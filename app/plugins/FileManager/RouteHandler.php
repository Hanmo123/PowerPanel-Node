<?php

namespace app\plugins\FileManager;

use app\Framework\Exception\TokenInvalidException;
use app\Framework\Model\Instance;
use app\Framework\Request\Middleware;
use app\plugins\FileManager\Exception\PathTraversalException;
use app\plugins\FileManager\Middleware\CORSMiddleware;
use app\plugins\FileManager\Middleware\TokenAuthMiddleware;
use app\plugins\Token\Token;
use League\Flysystem\PathTraversalDetected;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\Filesystem\Path;

// TODO 处理文件系统权限
class RouteHandler
{
    #[Middleware(CORSMiddleware::class)]
    static public function CORS(Request $request, Response $response)
    {
        $response->end();
    }

    static public function GetList(Request $request, Response $response)
    {
        try {
            $instance = Instance::Get($request->post['attributes']['uuid'], false);
            return [
                'code' => 200,
                'data' => $instance->getFileSystemHandler()->list(base64_decode($request->post['attributes']['path']))
            ];
        } catch (PathTraversalDetected) {
            throw new PathTraversalException;
        }
    }

    static public function Rename(Request $request, Response $response)
    {
        try {
            $instance = Instance::Get($request->post['attributes']['uuid'], false);

            $instance->getFileSystemHandler()->rename(
                base64_decode($request->post['attributes']['from']),
                base64_decode($request->post['attributes']['to'])
            );

            return ['code' => 200];
        } catch (PathTraversalDetected) {
            throw new PathTraversalException;
        }
    }

    static public function Delete(Request $request, Response $response)
    {
        try {
            $instance = Instance::Get($request->post['attributes']['uuid'], false);
            $handler  = $instance->getFileSystemHandler();

            $base = $handler->normalizePath(base64_decode($request->post['attributes']['base']));
            $handler->delete(
                array_map(fn (string $v) => $base . '/' . base64_decode($v), $request->post['attributes']['targets'])
            );

            return ['code' => 200];
        } catch (PathTraversalDetected) {
            throw new PathTraversalException;
        }
    }

    static public function Create(Request $request, Response $response)
    {
        try {
            $instance = Instance::Get($request->post['attributes']['uuid'], false);

            $handler = $instance->getFileSystemHandler();
            $handler->create(
                $request->post['attributes']['type'],
                $handler->normalizePath(base64_decode($request->post['attributes']['base'])) . '/' . base64_decode($request->post['attributes']['name'])
            );

            return ['code' => 200];
        } catch (PathTraversalDetected) {
            throw new PathTraversalException;
        }
    }

    static public function Read(Request $request, Response $response)
    {
        try {
            $instance = Instance::Get($request->post['attributes']['uuid'], false);

            return [
                'code' => 200,
                'attributes' => [
                    'content' => $instance->getFileSystemHandler()->read(base64_decode($request->post['attributes']['path']))
                ]
            ];
        } catch (PathTraversalDetected) {
            throw new PathTraversalException;
        }
    }

    static public function Save(Request $request, Response $response)
    {
        try {
            $instance = Instance::Get($request->post['attributes']['uuid'], false);

            $instance->getFileSystemHandler()->save(
                base64_decode($request->post['attributes']['path']),
                base64_decode($request->post['attributes']['content'])
            );

            return ['code' => 200];
        } catch (PathTraversalDetected) {
            throw new PathTraversalException;
        }
    }

    static public function GetPermission(Request $request, Response $response)
    {
        $instance = Instance::Get($request->post['attributes']['uuid'], false);

        return [
            'code' => 200,
            'attributes' => [
                'permission' => $instance->getFileSystemHandler()->getPermission(base64_decode($request->post['attributes']['path']))
            ]
        ];
    }

    static public function SetPermission(Request $request, Response $response)
    {
        $instance = Instance::Get($request->post['attributes']['uuid'], false);

        return [
            'code' => 200,
            'attributes' => [
                'permission' => $instance->getFileSystemHandler()->setPermission(
                    base64_decode($request->post['attributes']['path']),
                    $request->post['attributes']['permission']
                )
            ]
        ];
    }

    static public function Compress(Request $request, Response $response)
    {
        $instance = Instance::Get($request->post['attributes']['uuid'], false);

        $instance->getFileSystemHandler()->compress(
            base64_decode($request->post['attributes']['base']),
            array_map(fn (string $v) => base64_decode($v), $request->post['attributes']['targets'])
        );

        return ['code' => 200];
    }

    static public function Decompress(Request $request, Response $response)
    {;
        $instance = Instance::Get($request->post['attributes']['uuid'], false);

        $instance->getFileSystemHandler()->decompress(base64_decode($request->post['attributes']['path']));

        return ['code' => 200];
    }

    #[Middleware(TokenAuthMiddleware::class, CORSMiddleware::class)]
    static public function Upload(Request $request, Response $response)
    {
        $token = Token::Get($request->get['token']);
        if (!$token->isPermit('file.upload'))
            throw new \Exception('此密钥不可用于文件上传。', 401);

        $instance = Instance::Get($token->data['instance'], false);
        $handler = $instance->getFileSystemHandler();
        $symfony = $handler->getSymfony();
        $base = Path::canonicalize($handler->getBasePath() . $handler->normalizePath(base64_decode($token->data['base'])));
        // 确保基路径在容器目录下
        if ($handler->isTraversal($base))
            throw new \Exception('路径不合法。', 400);

        try {
            if (isset($request->get['slice']) && $request->get['slice'] == 1) {
                // 切片上传模式
                foreach ($request->files as $file) {
                    $target = $base . '/' . $file['name'];
                    // 处理不一定存在的问题
                    if ($handler->isTraversal($target, $base))
                        throw new \Exception('路径不合法。', 400);

                    if ($symfony->exists($target)) {
                        // 文件已存在
                        if ($request->get['first'] == 1) {
                            // 切片为首个切片 即文件为遗留文件
                            $symfony->rename($file['tmp_name'], $target, true);
                        } else {
                            file_put_contents(
                                $target,
                                file_get_contents($file['tmp_name']),
                                FILE_APPEND
                            );
                        }
                    } else {
                        // 文件不存在
                        $symfony->rename($file['tmp_name'], $target);
                    }
                }
            } else {
                // 小文件上传模式
                foreach ($request->files as $file) {
                    $target = $base . '/' . $file['name'];
                    // 处理不一定存在的问题
                    if ($handler->isTraversal($target, $base))
                        throw new \Exception('路径不合法。', 400);

                    $symfony->rename($file['tmp_name'], $target);
                }
            }
        } catch (\Throwable $th) {
            // 若为空文件可能会抛出异常
        }

        return ['code' => 200];
    }

    #[Middleware(TokenAuthMiddleware::class)]
    static public function Download(Request $request, Response $response)
    {
        $token = Token::Get($request->get['token']);
        if (!$token->isPermit('file.download'))
            throw new TokenInvalidException('此密钥不可用于文件下载', 401);

        $instance = Instance::Get($token->data['instance'], false);
        $handler = $instance->getFileSystemHandler();
        $path = Path::canonicalize($handler->getBasePath() . $handler->normalizePath(base64_decode($token->data['path'])));
        // 确保基路径在容器目录下
        if ($handler->isTraversal($path))
            throw new \Exception('路径不合法。', 400);
        if (!is_file($path))
            throw new \Exception('文件不存在。', 404);

        $response->header('Content-Disposition', 'attachment; filename="' . basename($path) . '"');
        $response->header('Content-type', 'application/octet-stream');
        $response->sendfile($path);
    }
}

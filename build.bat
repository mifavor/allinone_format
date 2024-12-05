:: 设置 UTF-8 编码
chcp 65001
@echo off
setlocal enabledelayedexpansion

title Docker 多架构镜像构建脚本

:: 添加版本选择
echo 请选择要构建的版本:
echo [1] latest (默认)
echo [2] dev

:: 提示用户输入选择，并提供默认选项
set "TAG=latest"  :: 默认值为 'latest'
set /p "userChoice=请输入选择 (直接回车选择默认 latest): "

if "%userChoice%"=="2" set "TAG=dev"

echo.
echo 已选择版本: %TAG%
echo 开始构建 - %date% %time%

:: 编译前端代码
echo [1/9] 进入前端目录...
cd frontend || (
    echo [错误] 找不到前端目录
    exit /b 1
)

echo [2/9] 安装前端依赖...
:: 判断 node_modules 是否存在，不存在则安装，存在则 npm update
if not exist "node_modules" (
    call npm install
    if errorlevel 1 (
        echo [错误] npm install 失败
        pause
        exit /b 1
    )
) else (
    call npm update
    if errorlevel 1 (
        echo [错误] npm update 失败
        pause
        exit /b 1
    )
)

echo [3/9] 构建前端...
call npm run build
if errorlevel 1 (
    echo [错误] npm build 失败
    pause
    exit /b 1
)

echo [4/9] 返回根目录...
cd ..

echo [5/9] 进入后端目录...
cd server

echo [6/9] 清理配置文件...
if exist "config\*" (
    echo 正在清理配置文件...
    del /q "config\*.*" 2>nul || (
        echo [错误] 配置文件清理失败
        pause
        exit /b 1
    )
)

echo [7/9] 返回根目录...
cd ..

echo [8/9] 构建镜像...
docker buildx build --pull --platform linux/amd64,linux/arm64,linux/arm/v7 -t yuexuangu/allinone_format:%TAG% .
if errorlevel 1 (
    echo [错误] 镜像构建失败
    pause
    exit /b 1
)

echo [9/9] 推送镜像...
set "PUSH=Y"  :: 默认所有情况下都设置为 Y

set /p "pushChoice=是否推送镜像? (直接回车推送, 输入 2 跳过): "
if "%TAG%"=="dev" (
    if "%pushChoice%"=="2" set "PUSH=N"
)

if "%PUSH%"=="Y" (
    docker push yuexuangu/allinone_format:%TAG%
    if errorlevel 1 (
        echo [错误] 镜像推送失败
        pause
        exit /b 1
    )
) else (
    echo 跳过镜像推送
)

echo.
echo 构建完成 - %date% %time%

exit /b
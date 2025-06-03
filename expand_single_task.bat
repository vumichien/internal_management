@echo off
setlocal enabledelayedexpansion

if "%1"=="" (
    echo Usage: expand_single_task.bat [task_id]
    echo Example: expand_single_task.bat 1
    pause
    exit /b 1
)

set "TASK_ID=%1"
set "COMPLEXITY_REPORT=.taskmaster\reports\task-complexity-report.json"
set "EXPANDED_LOG=.taskmaster\reports\expanded-tasks.log"

echo ========================================
echo Expanding Task ID: %TASK_ID%
echo ========================================

:: Check if complexity report exists
if not exist "%COMPLEXITY_REPORT%" (
    echo Error: Complexity report not found at %COMPLEXITY_REPORT%
    echo Please run: task-master analyze-complexity first
    pause
    exit /b 1
)

:: Create expanded tasks log if it doesn't exist
if not exist "%EXPANDED_LOG%" (
    echo. > "%EXPANDED_LOG%"
)

:: Check if task was already expanded
findstr /C:"EXPANDED:%TASK_ID%" "%EXPANDED_LOG%" >nul
if %errorlevel% equ 0 (
    echo Task %TASK_ID% has already been expanded.
    echo Check %EXPANDED_LOG% for details.
    pause
    exit /b 0
)

:: Create temporary PowerShell script
echo $json = Get-Content '%COMPLEXITY_REPORT%' ^| ConvertFrom-Json > temp_ps_script.ps1
echo $task = $json.complexityAnalysis ^| Where-Object { $_.taskId -eq %TASK_ID% } >> temp_ps_script.ps1
echo if ($task) { >> temp_ps_script.ps1
echo     Write-Output "FOUND:$($task.taskId)" >> temp_ps_script.ps1
echo     Write-Output "TITLE:$($task.taskTitle)" >> temp_ps_script.ps1
echo     Write-Output "SUBTASKS:$($task.recommendedSubtasks)" >> temp_ps_script.ps1
echo     Write-Output "PROMPT:$($task.expansionPrompt)" >> temp_ps_script.ps1
echo } else { >> temp_ps_script.ps1
echo     Write-Output "NOT_FOUND" >> temp_ps_script.ps1
echo } >> temp_ps_script.ps1

:: Run PowerShell script
powershell.exe -NoProfile -ExecutionPolicy Bypass -File temp_ps_script.ps1 > temp_task_info.txt

:: Clean up PowerShell script
del temp_ps_script.ps1

:: Read task information
set "task_found=false"
set "task_title="
set "num_subtasks="
set "prompt="

for /f "usebackq delims=" %%a in ("temp_task_info.txt") do (
    set "line=%%a"
    
    if "!line:~0,6!"=="FOUND:" (
        set "task_found=true"
    ) else if "!line:~0,6!"=="TITLE:" (
        set "task_title=!line:~6!"
    ) else if "!line:~0,9!"=="SUBTASKS:" (
        set "num_subtasks=!line:~9!"
    ) else if "!line:~0,7!"=="PROMPT:" (
        set "prompt=!line:~7!"
    ) else if "!line!"=="NOT_FOUND" (
        echo Task ID %TASK_ID% not found in complexity report.
        del temp_task_info.txt
        pause
        exit /b 1
    )
)

del temp_task_info.txt

if "!task_found!"=="false" (
    echo Task ID %TASK_ID% not found in complexity report.
    pause
    exit /b 1
)

echo Task Title: !task_title!
echo Recommended Subtasks: !num_subtasks!
echo.
echo Expansion Prompt:
echo !prompt!
echo.

:: Confirm before expanding
set /p "confirm=Do you want to expand this task? (y/n): "
if /i not "!confirm!"=="y" (
    echo Operation cancelled.
    pause
    exit /b 0
)

:: Log the attempt
echo [%date% %time%] ATTEMPTING: Task %TASK_ID% - !task_title! >> "%EXPANDED_LOG%"

:: Run the expand command
echo.
echo Running expansion command...
task-master expand --id=%TASK_ID% --num=!num_subtasks! --prompt="!prompt!"

:: Check if command was successful
if %errorlevel% equ 0 (
    echo [%date% %time%] SUCCESS: Task %TASK_ID% expanded successfully >> "%EXPANDED_LOG%"
    echo EXPANDED:%TASK_ID% >> "%EXPANDED_LOG%"
    echo.
    echo ✓ Task %TASK_ID% expanded successfully
) else (
    echo [%date% %time%] ERROR: Task %TASK_ID% expansion failed with error code %errorlevel% >> "%EXPANDED_LOG%"
    echo.
    echo ✗ Task %TASK_ID% expansion failed
)

echo.
pause 
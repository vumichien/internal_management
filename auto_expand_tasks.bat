@echo off
setlocal enabledelayedexpansion

echo ========================================
echo Auto Task Expansion Script
echo ========================================

:: Set paths
set "COMPLEXITY_REPORT=.taskmaster\reports\task-complexity-report.json"
set "EXPANDED_LOG=.taskmaster\reports\expanded-tasks.log"
set "TEMP_JSON=temp_complexity.json"

:: Check if complexity report exists
if not exist "%COMPLEXITY_REPORT%" (
    echo Error: Complexity report not found at %COMPLEXITY_REPORT%
    echo Please run: task-master analyze-complexity first
    pause
    exit /b 1
)

:: Create expanded tasks log if it doesn't exist
if not exist "%EXPANDED_LOG%" (
    echo Creating expanded tasks log...
    echo. > "%EXPANDED_LOG%"
)

echo Reading complexity report...
echo.

:: Create temporary PowerShell script
echo $json = Get-Content '%COMPLEXITY_REPORT%' ^| ConvertFrom-Json > temp_ps_script.ps1
echo $expandedTasks = @() >> temp_ps_script.ps1
echo if (Test-Path '%EXPANDED_LOG%') { >> temp_ps_script.ps1
echo     $expandedTasks = Get-Content '%EXPANDED_LOG%' ^| Where-Object { $_ -match '^EXPANDED:' } ^| ForEach-Object { ($_ -split ':')[1] } >> temp_ps_script.ps1
echo } >> temp_ps_script.ps1
echo foreach ($task in $json.complexityAnalysis) { >> temp_ps_script.ps1
echo     if ($expandedTasks -notcontains $task.taskId.ToString()) { >> temp_ps_script.ps1
echo         Write-Output "TASK_ID:$($task.taskId)" >> temp_ps_script.ps1
echo         Write-Output "TASK_TITLE:$($task.taskTitle)" >> temp_ps_script.ps1
echo         Write-Output "RECOMMENDED_SUBTASKS:$($task.recommendedSubtasks)" >> temp_ps_script.ps1
echo         Write-Output "EXPANSION_PROMPT:$($task.expansionPrompt)" >> temp_ps_script.ps1
echo         Write-Output "---" >> temp_ps_script.ps1
echo     } >> temp_ps_script.ps1
echo } >> temp_ps_script.ps1

:: Run PowerShell script
powershell.exe -NoProfile -ExecutionPolicy Bypass -File temp_ps_script.ps1 > temp_tasks.txt

:: Clean up PowerShell script
del temp_ps_script.ps1

:: Check if there are tasks to expand
if not exist temp_tasks.txt (
    echo No tasks found to expand.
    goto :cleanup
)

:: Read and process each task
set "current_task_id="
set "current_title="
set "current_subtasks="
set "current_prompt="

for /f "usebackq delims=" %%a in ("temp_tasks.txt") do (
    set "line=%%a"
    
    if "!line:~0,8!"=="TASK_ID:" (
        set "current_task_id=!line:~8!"
    ) else if "!line:~0,11!"=="TASK_TITLE:" (
        set "current_title=!line:~11!"
    ) else if "!line:~0,21!"=="RECOMMENDED_SUBTASKS:" (
        set "current_subtasks=!line:~21!"
    ) else if "!line:~0,17!"=="EXPANSION_PROMPT:" (
        set "current_prompt=!line:~17!"
    ) else if "!line!"=="---" (
        if defined current_task_id (
            call :expand_task "!current_task_id!" "!current_title!" "!current_subtasks!" "!current_prompt!"
        )
        set "current_task_id="
        set "current_title="
        set "current_subtasks="
        set "current_prompt="
    )
)

:cleanup
if exist temp_tasks.txt del temp_tasks.txt
echo.
echo ========================================
echo Auto expansion completed!
echo Check %EXPANDED_LOG% for execution log
echo ========================================
pause
exit /b 0

:expand_task
set "task_id=%~1"
set "task_title=%~2"
set "num_subtasks=%~3"
set "prompt=%~4"

echo.
echo ----------------------------------------
echo Expanding Task ID: %task_id%
echo Title: %task_title%
echo Subtasks: %num_subtasks%
echo ----------------------------------------

:: Log the attempt
echo [%date% %time%] ATTEMPTING: Task %task_id% - %task_title% >> "%EXPANDED_LOG%"

:: Run the expand command
echo Running: task-master expand --id=%task_id% --num=%num_subtasks% --prompt="%prompt%"
task-master expand --id=%task_id% --num=%num_subtasks% --prompt="%prompt%"

:: Check if command was successful
if %errorlevel% equ 0 (
    echo [%date% %time%] SUCCESS: Task %task_id% expanded successfully >> "%EXPANDED_LOG%"
    echo EXPANDED:%task_id% >> "%EXPANDED_LOG%"
    echo ✓ Task %task_id% expanded successfully
) else (
    echo [%date% %time%] ERROR: Task %task_id% expansion failed with error code %errorlevel% >> "%EXPANDED_LOG%"
    echo ✗ Task %task_id% expansion failed
)

echo.
timeout /t 2 /nobreak >nul
goto :eof 
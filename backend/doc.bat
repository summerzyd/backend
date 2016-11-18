@echo off
if "%1" == "m" (.\vendor\bin\phpdoc -d app\Models\ -t ..\doc-model --template="model") ^
else (.\vendor\bin\phpdoc -d app\Http\Controllers\ -t ..\doc-controller --template="controller")
pause

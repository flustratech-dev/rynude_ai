Set objShell = CreateObject("WScript.Shell")
objShell.Run "cmd.exe /c start /b node cli.js --silent", 0, False

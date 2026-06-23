' Start-Rynude.vbs
' Script peluncur untuk menghilangkan kilatan jendela hitam/biru terminal

Set WshShell = CreateObject("WScript.Shell")

' Mendapatkan direktori saat ini (tempat file VBS ini berada)
strPath = Wscript.ScriptFullName
Set objFSO = CreateObject("Scripting.FileSystemObject")
Set objFile = objFSO.GetFile(strPath)
strFolder = objFSO.GetParentFolderName(objFile)

' Menjalankan rynude-tray.ps1 dengan window style hidden (0) dan mode STA (wajib untuk System Tray)
WshShell.Run "powershell.exe -STA -ExecutionPolicy Bypass -WindowStyle Hidden -File """ & strFolder & "\rynude-tray.ps1""", 0, False

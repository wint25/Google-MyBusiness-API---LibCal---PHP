@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../apigen/apigen/bin/apigen
php "%BIN_TARGET%" %*

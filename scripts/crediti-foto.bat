@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
title Oltr'Alpe - Credito autore foto (EXIF)

rem ============================================================================
rem  Inscrit le nom de l'auteur dans les metadonnees EXIF/IPTC/XMP des photos
rem  d'un dossier, AVANT de les uploader dans le CMS.
rem  La galerie lit ensuite ce credit et l'affiche dans la lightbox.
rem
rem  Prerequis : installer exiftool une seule fois -> https://exiftool.org
rem  (telecharger "Windows Executable", renommer exiftool(-k).exe en exiftool.exe
rem   et le placer dans ce dossier scripts\ ou dans un dossier du PATH).
rem
rem  Usage : glisser le dossier de photos sur ce .bat, OU double-cliquer et
rem  coller le chemin quand c'est demande.
rem ============================================================================

echo.
echo   === Credito autore delle foto (Oltr'Alpe) ===
echo.

rem --- Localiser exiftool : dans ce dossier, sinon dans le PATH ---
set "EXIFTOOL=%~dp0exiftool.exe"
if not exist "%EXIFTOOL%" set "EXIFTOOL=exiftool"
where "%EXIFTOOL%" >nul 2>nul
if errorlevel 1 (
  if not exist "%~dp0exiftool.exe" (
    echo   [!] exiftool introuvable.
    echo       Telechargez-le sur https://exiftool.org et placez exiftool.exe
    echo       dans ce dossier ^(scripts\^) ou dans le PATH, puis relancez.
    echo.
    pause
    exit /b 1
  )
)

rem --- Dossier des photos : argument glisse-depose, sinon saisie ---
set "FOLDER=%~1"
if "%FOLDER%"=="" set /p "FOLDER=  Dossier des photos (glisser-deposer ou coller le chemin) : "
set "FOLDER=%FOLDER:"=%"
if not exist "%FOLDER%\" (
  echo   [!] Dossier introuvable : %FOLDER%
  echo.
  pause
  exit /b 1
)

rem --- Nom de l'auteur ---
set "AUTORE="
set /p "AUTORE=  Nom de l'auteur des photos : "
if "%AUTORE%"=="" (
  echo   [!] Aucun nom saisi, abandon.
  echo.
  pause
  exit /b 1
)

echo.
echo   Application du credit "%AUTORE%" aux photos de :
echo   %FOLDER%
echo.

"%EXIFTOOL%" -overwrite_original -charset filename=UTF8 ^
  -ext jpg -ext jpeg -ext png -ext tif -ext tiff ^
  -Artist="%AUTORE%" -XPAuthor="%AUTORE%" ^
  -XMP-dc:Creator="%AUTORE%" -IPTC:By-line="%AUTORE%" ^
  -Copyright="(C) %AUTORE%" ^
  "%FOLDER%"

echo.
echo   Termine. Vous pouvez maintenant uploader ces photos dans le CMS.
echo.
pause
endlocal

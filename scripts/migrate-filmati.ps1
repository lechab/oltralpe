# Script jetable : extrait les videos YouTube des pages pagina_filmato*/pagina_evento*
# et genere data/filmati.json. A relancer si besoin ; le resultat est ensuite affine via le CMS.

Add-Type -AssemblyName System.Web

$root     = Split-Path -Parent $PSScriptRoot
$pagesDir = Join-Path $root 'pages'
$outFile  = Join-Path $root 'data\filmati.json'

$enc1252  = [System.Text.Encoding]::GetEncoding(1252)

$iframeRx = [regex]'(?is)<iframe\b[^>]*?youtube\.com/embed/([A-Za-z0-9_\-]+)'
$st77Rx   = [regex]'(?is)<span class="Stile77">(.*?)</span>'
$st84Rx   = [regex]'(?is)<span class="Stile84">(.*?)</span>'
$st82Rx   = [regex]'(?is)<span class="Stile82">(.*?)</span>'
$yearRx   = [regex]'\b(19\d\d|20[0-3]\d)\b'
$creditRx = [regex]'(?i)(?:realizzazione di|elaborazione video di|video di|a cura di)\s+([^<\)\.]+)'

function Clean-Text([string]$s) {
  if (-not $s) { return '' }
  $s = [regex]::Replace($s, '(?is)<[^>]+>', ' ')       # retire les balises
  $s = [System.Web.HttpUtility]::HtmlDecode($s)         # &agrave; -> a accentue
  $s = [regex]::Replace($s, '\s+', ' ')                 # espaces multiples
  return $s.Trim()
}

$videos = @()
$seen   = @{}

$files = Get-ChildItem -Path $pagesDir -Filter 'pagina_*.html' |
         Where-Object { $_.Name -like 'pagina_filmato*' -or $_.Name -like 'pagina_evento*' } |
         Sort-Object Name

foreach ($f in $files) {
  $html  = [System.IO.File]::ReadAllText($f.FullName, $enc1252)
  $theme = if ($f.Name -like 'pagina_filmato*') { 'camminata' } else { 'evento' }

  $matches = $iframeRx.Matches($html)
  $prevEnd = 0
  foreach ($m in $matches) {
    $id = $m.Groups[1].Value
    # fenetre de texte precedant cet iframe (depuis l'iframe precedent)
    $winStart = $prevEnd
    $winLen   = $m.Index - $winStart
    $window   = if ($winLen -gt 0) { $html.Substring($winStart, $winLen) } else { '' }
    $prevEnd  = $m.Index + $m.Length

    # titre : spans Stile77 (sous-titre/lieu), sinon Stile84, sinon Stile82
    $titleParts = @()
    foreach ($tm in $st77Rx.Matches($window)) { $t = Clean-Text $tm.Groups[1].Value; if ($t) { $titleParts += $t } }
    if ($titleParts.Count -eq 0) { foreach ($tm in $st84Rx.Matches($window)) { $t = Clean-Text $tm.Groups[1].Value; if ($t) { $titleParts += $t } } }
    if ($titleParts.Count -eq 0) { foreach ($tm in $st82Rx.Matches($window)) { $t = Clean-Text $tm.Groups[1].Value; if ($t) { $titleParts += $t } } }
    $title = ($titleParts | Select-Object -Last 1)
    if (-not $title) { $title = '(senza titolo)' }

    # annee : dernier millesime trouve dans la fenetre
    $year = ''
    $ym = $yearRx.Matches($window)
    if ($ym.Count -gt 0) { $year = $ym[$ym.Count - 1].Groups[1].Value }

    # realisateur
    $credit = ''
    $cm = $creditRx.Match($window)
    if ($cm.Success) { $credit = (Clean-Text $cm.Groups[1].Value) }

    if ($seen.ContainsKey($id)) { continue }   # dedup par ID YouTube
    $seen[$id] = $true

    $videos += [ordered]@{
      title       = $title
      url         = "https://www.youtube.com/embed/$id"
      year        = $year
      location    = ''
      theme       = $theme
      credit      = $credit
      description = ''
    }
  }
}

# tri par annee decroissante (les sans-annee a la fin)
$videos = $videos | Sort-Object @{ Expression = { if ($_.year) { [int]$_.year } else { 0 } }; Descending = $true }, title

$obj  = [ordered]@{ videos = $videos }
$json = $obj | ConvertTo-Json -Depth 6

# ConvertTo-Json (PS 5.1) echappe tout en \uXXXX : on redecode en UTF-8 lisible
$json = [regex]::Replace($json, '\\u([0-9a-fA-F]{4})', { param($m) [char][int]('0x' + $m.Groups[1].Value) })

# ecriture UTF-8 sans BOM
$utf8 = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($outFile, $json, $utf8)

Write-Output "Videos extraites : $($videos.Count)"
Write-Output "Ecrit : $outFile"

<?php

$fontIdentifier     = '';   // e.g. "FontAwesome"
$fontDescription    = '';   // e.g. "Font Awesome v4.5.0"
$iconTypePrefix     = '';   // e.g. "FA"
$fontFile           = '';   // e.g. "FontAwesome.otf"
$fontName           = null; // e.g. "FontAwesome" or null to use the filename of $fontFile
$iconIdentifiers    = [];   // e.g. ['fa-wifi-o']
$iconNames          = [];   // e.g. ['wifi-o']
$iconCodes          = [];   // e.g. ['f01a']
$moveToProject      = true; // determines move .h and .m files to Xcode project

function CodeGenerator()
{
    global $fontIdentifier, $fontDescription, $iconTypePrefix, $fontFile, $fontName;
    global $iconIdentifiers, $iconNames, $iconCodes;
    global $moveToProject;

    date_default_timezone_set('Asia/Shanghai');

    $header = "//\n";
    $header .= "// $fontDescription\n";
    $header .= "// Generated on " . date('Y-m-d') . "\n";
    $header .= "//\n\n";
    $className = "IF$fontIdentifier";
    $tab = "    ";

    $hContent = $header;
    $hContent .= "#import <IconFontsKit/IFIcon.h>\n\n";
    $hContent .= "typedef NS_ENUM(IFIconType, {$className}Type) {\n";
    for ($i = 0; $i < count($iconIdentifiers); ++$i) {
        $name = $iconNames[$i];
        $name = ucfirst($name);
        $name = preg_replace_callback("#[-_.]+[0-9a-z]{1}#i",
            function($matches) {
                return strtoupper(preg_replace("#[-_.]#", '', $matches[0]));
            }, $name);
        $type = sprintf("{$tab}IF%s%-35s = 0x%s,\n", $iconTypePrefix, $name, $iconCodes[$i]);
        $hContent .= $type;
    }
    $hContent .= "};\n\n";
    $hContent .= "@interface $className : IFIcon\n";
    $hContent .= "@end\n";

    $mContent = $header;
    $mContent .= "#import \"$className.h\"\n\n";
    $mContent .= "@implementation $className\n";
    $mContent .= "\n+ (NSURL *)fontFileURL\n{\n";
    $mContent .= "{$tab}return [[[NSBundle mainBundle] resourceURL] URLByAppendingPathComponent:@\"$fontFile\"];\n";
    $mContent .= "}\n";
    if (!empty($fontName)) {
        $mContent .= "\n+ (NSString *)fontName\n{\n";
        $mContent .= "{$tab}return @\"$fontName\";\n";
        $mContent .= "}\n";
    }
    $mContent .= "\n+ (NSDictionary *)allIcons\n{\n";
    $mContent .= "{$tab}return @{\n";
    for ($i = 0; $i < count($iconIdentifiers); ++$i) {
        $idKey = "@\"$iconIdentifiers[$i]\": ";
        $mContent .= sprintf("%13s%-44s@\"\\u%s\",\n", " ", $idKey, $iconCodes[$i]);
    }
    $mContent .= sprintf("%13s};\n", " ");
    $mContent .= "}\n";

    $mContent .= "\n@end\n";

    file_put_contents("$className.h", $hContent);
    file_put_contents("$className.m", $mContent);

    if ($moveToProject) {
        rename("$className.h", "../../IconFontsKit/$className.h");
        rename("$className.m", "../../IconFontsKit/$className.m");
    }

    echo "$fontIdentifier contains " . count($iconIdentifiers) . " icons (including aliases).\n";
}

/**
 * Parse CSS file to get icon names and codes.
 *
 * CSS file looks like: .iconPrefix-icon-name:... { xxxx "\f001" }
 * e.g.
 *
 *   .fa-play-circle-o:before {
 *     content: "\f01d";
 *   }
 *
 *   .fa-rotate-right:before,
 *   .fa-repeat:before {
 *     content: "\f01e";
 *   }
 *
 *   .ion-android-add:before { content: "\f2c7"; }
 *
 *   .zocial.aol:before { content: "\f104"; }
 *
 */
function parseCSS($cssFile, $iconPrefix)
{
    global $iconNames, $iconCodes, $iconIdentifiers;

    $variables = file_get_contents($cssFile);
    $variables = preg_replace_callback("#^\\.{$iconPrefix}[-_.][^,\\n]+,[^{]+({[^}]+})#im", function($matches) {
        return str_replace(',', $matches[1], $matches[0]);
    }, $variables);

    if (preg_match_all("#^\\.{$iconPrefix}([-_.])([^:]+):[^\"'}]*[\"']\\\\([0-9a-f]+)#im", $variables, $matches)) {
        $iconNames = $matches[2];
        $iconCodes = $matches[3];
        foreach ($matches[2] as $str) {
            $iconIdentifiers[] = $iconPrefix . $matches[1][0] . $str;
        }
    }
}

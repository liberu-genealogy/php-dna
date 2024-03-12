namespace src\Utils;

class ColorSchemeGenerator {
    public static function generate($numColors) {
        if ($numColors < 1) {
            return [];
        }
        $colors = [];
        for ($i = 0; $i < $numColors; $i++) {
            $hue = ($i * 360 / $numColors) % 360;
            $colors[] = "hsl(" . $hue . ", 100%, 50%)";
        }
        return $colors;
    }
}

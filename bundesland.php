<?php

declare(strict_types=1);
class Bundesland
{
    static function get($bundesland): String
    {
        $bl = intval($bundesland);
        if (($bl < 1) || ($bl > 16)) {
            return  "IdLandkreis > 0";
        } else {
            return "IdLandkreis >= " . $bl . "000 AND IdLandkreis < " . ($bl + 1) . "000";
        }
    }

    static function getNameIn($bundesland): String
    {
        $result = Bundesland::getName($bundesland);
        if (empty($result)){
            return "";
        } else {
            return "in ".$result;
        }
    }


    static function getName($bundesland): String
    {
        $bl = intval($bundesland);
        switch ($bl) {
            case 1:
                return "Schleswig-Holstein";
                break;
            case 2:
                return "Hamburg";
                break;
            case 3:
                return "Niedersachsen";
                break;
            case 4:
                return "Bremen";
                break;
            case 5:
                return "Nordrhein-Westfalen";
                break;
            case 6:
                return "Hessen";
                break;
            case 7:
                return "Rheinland-Pfalz";
                break;
            case 8:
                return "Baden-Württemberg";
                break;
            case 9:
                return "Bayern";
                break;
            case 10:
                return "Saarland";
                break;
            case 11:
                return "Berlin";
                break;
            case 12:
                return "Brandenburg";
                break;
            case 13:
                return "Mecklenburg-Vorpommern";
                break;
            case 14:
                return "Sachsen";
                break;
            case 15:
                return "Sachsen-Anhalt";
                break;
            case 16:
                return "Thüringen";
                break;
            default:
                return "";
        }
    }
}

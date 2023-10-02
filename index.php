<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XML Chunker</title>
</head>
<body>
    <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    require_once('Chunker.php');

    function passesValidation($data, $tag): bool{
        switch ($tag) {
            case 'weight_kg':
                // suly, nem lehet ures es nagyobbnak kell lennie mint 0
                if(!empty($data) && intval($data) > 0){
                    return true;
                }else{
                    return false;
                }
                break;
            
            case 'categoryText':
                $excludedItems = array("Burkolatok", "Padlóburkolatok", "Fürdőszobai csempe és burkolat", "Magasított álló kádcsapok", "Bidézuhany hideg vizes", "Bidézuhany fejek", "Orvosi karos csaptelepek", "Önzáró piszoár szelepek", "Önzáró zuhany szelepek", "Önzáró mosdó szelepek", "Önzáró WC szelepek", "Tartalék alkatrészek csaptelephez", "Szűrő rendszer", "Fürdőszobai kiegészítők", "Hidromasszázs rendszerek POLYSAN", "Világítás kádba", "Hidromasszázs kádak", "Vonalvezető épített zuhanyhoz", "Kiegészítő gyógyszertartó", "Tükrös szekrények | Materiál plast | PVC", "Tükrös szekrények | PVC", "Tükrös szekrények | PUBLIC", "Kiegészítők bútorokhoz", "Konzolok és pult tartók", "Kis bútorok WC-be | LATUS XI", "Kis bútorok WC-be | LATUS VI<", "Kis bútorok WC-be | LATUS VI ", "| Mosogatók |", "Fali kiöntők", "Öblítési módok", "Öblítéshez tápegység", "Piszoár térelválasztók", "Piszoár Kiegészítők", "WC elektronikus bidével", "WC-k bidézuhannyal és szeleppel vagy csapteleppel", "Közösségi helyiségbe", "Sarok nyílóajtós kabin", "Téglalap alakú zuhanykabin", "Íves aszimmetrikus zuhanykabin", "L- alakú fix fal nyitható résszel", "Háromoldalú zuhanykabinok", "Zuhanykabinok mély tálcával", "Kádparaván, pneumatikus működés", "Zuhanykabinok | Kiegészítők", "Ventilátorok", "Padlófűtések", "Radiátor szelepek", "Fogasok, törölközőszárítók", "Rozetták takaró idomok", "Szorítógyűrű", "Radiátor kiegészítők | Egyéb kiegészítők", "Elektromos radiátorok", "Elektromos törölköző szárítók", "Infrapanelek", "Bűzelzárók kádhoz", "| Mosógép |", "| Mosogató |", "| Bidé |", "Takaró elemek", "Festékek, tömítőanyagok, tisztítószerek, javítási kellékek", "Sarokszelepek", "Folyókák", "Kiegészítők | Szerelési kellékek", "Szerelési kellékek | Kiegészítők", "Kerti szelepek", "Rozetták és takaró elemek", "Kéziszerszámok és kiegészítők", "Nyomólapok", "Fali tartályok, rendszerek | Kiegészítők", "Fali tartályok rendszerek | Modulok | Szerelési kellékek | Kiegészítők", "Konyhai kiegészítők", "Aqualine konyhák");
                foreach($excludedItems as $item){
                    if(str_contains($data, $item)) return false;
                }
                return true;
                break;
            
            case 'product':
                $excludedItems = array("SITIA mosdótartó szekrény", "CIRASA Mosdótartó szekrény", "LARGO Mosdótartó szekrény", "SKA fiókos mosdótartó", "PUNO mosdótartó", "LUCIE Fiókos mosdótartó szekrény", "FERRO mosdótartó", "MORIAN mosdótartó", "VIERA mosdótartó", "CIMBURA mosdótartó", "ALTAIR mosdótartó", "VEGA mosdótartó szekrény", "ZOJA mosdótartó szekrény", "KERAMIA FRESH mosdótartó szekrény", "NEON mosdótartószekrény", "VEGA mosdótartó pult", "BRAND mosdótartó", "zuhanybox", );
                foreach($excludedItems as $item){
                    if(str_contains($data, $item)) return false;
                }
                return true;
                break;

            case 'serie':
                //$excludedItems = array("WOODY desky");
                if(str_contains($data, "WOODY desky")) return false;
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    $checkTags = array("weight_kg", "categoryText", "product", "serie");
    $outputFilePrefix = "output-";
    $chunker = new Chunker("saphoseed20230311123839361.xml", 1000, $outputFilePrefix, "passesValidation", $checkTags);
    $log = $chunker->chunkXML("shopItem", "Shop", "UTF-8");
    echo $log;


    ?>
</body>
</html>
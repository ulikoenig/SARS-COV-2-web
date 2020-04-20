<?php
declare(strict_types=1);
include_once("config.php");
define("DOWNLOADURL", "https://github.com/ulikoenig/SARS-COV-2/raw/master/RKI-CSV/Latest-RKI.csv");
/*$url = 'https://opendata.arcgis.com/datasets/dd4580c810204019a7b8eb3e0b329dd6_0.csv'; */



$obj = new LoadRKI();
$obj->update();
$obj->updateKreise();

class LoadRKI
{
    private $TABLE;
    private $link;

    function __construct()
    {
        include_once("connect.php");
        $dbInstance = ConnectDB::getInstance();
        $this->TABLE =  $dbInstance::TABLE;
        $this->link = $dbInstance->link;
    }

    function __destruct()
    {
        // Close connection
        mysqli_close($this->link);
    }

    function flushBuffer()
    {
        if (php_sapi_name() != 'cli') {
            ob_flush();
        }
        flush();
    }

    function update()
    {
        $sql = "DROP TABLE " . $this->TABLE . ";";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- DROP successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }
        $this->flushBuffer();

        $sql = "CREATE TABLE " . $this->TABLE . " (IdBundesland int, Bundesland varchar(32), Landkreis varchar(32), Altersgruppe varchar(9), Geschlecht varchar(1), AnzahlFall int, AnzahlTodesfall int, ObjectId int, Meldedatum DATE, IdLandkreis int, DatenstandChar varchar(32), NeuerFall int, NeuerTodesfall int, Refdatum DATE, NeuGenesen int, AnzahlGenesen int, Datenstand DATE, PRIMARY KEY (ObjectId));";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Table created successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }
        $this->flushBuffer();

        // Use basename() function to return the base name of file  
        $filename = TEMPDIR . $this->TABLE . ".csv";

        // Use file_get_contents() function to get the file 
        // from url and use file_put_contents() function to 
        // save the file by using base name 
        if (file_put_contents($filename, file_get_contents(DOWNLOADURL))) {
            if (DEBUG) echo "<!-- File downloaded successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- File downloading failed. -->\n";
        }
        $this->flushBuffer();

        if (file_exists($filename)) {
            if (DEBUG) echo "<!-- Die Datei $filename existiert -->\n";
        } else {
            if (DEBUG) echo "<!-- Die Datei $filename existiert nicht -->\n";
        }
        $this->flushBuffer();

        $sql = "LOAD DATA LOCAL INFILE '$filename' IGNORE INTO TABLE " . $this->TABLE . " CHARACTER SET UTF8 FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\n' IGNORE 1 LINES;";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- IMPORT successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }
        $this->flushBuffer();

        if(unlink ($filename ) ) {
            if (DEBUG) echo "<!-- Datei $filename erfolgreich gelöscht -->\n";
        } else {
            if (DEBUG) echo "<!-- Datei $filename konnte nicht gelöscht werden -->\n";
        }

        $sql = "UPDATE " . $this->TABLE . " SET Datenstand = STR_TO_DATE(DatenstandChar,'%d.%m.%Y, %H:%i Uhr')";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- UPDATE successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }
        $this->flushBuffer();

        $sql = "ALTER TABLE " . $this->TABLE . " DROP COLUMN DatenstandChar;";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- ALTER TABLE successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }
        $this->flushBuffer();

        $sql = "SELECT * FROM " . $this->TABLE . ";";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- SELECT successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }
        $this->flushBuffer();
    }





    function updateKreise()
    {
        $KREISTABLE = "Kreise";

        $sql = "DROP TABLE " . $KREISTABLE . ";";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- DROP successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }
        $this->flushBuffer();

        $sql = "CREATE TABLE " . $KREISTABLE . " (Krs int, Landkreis varchar(35), UZ varchar(44), Bundesland varchar(2), Kreissitz varchar(32), Einwohner int, Flaeche int, Bevoelkerungsdichte NUMERIC(6, 2), PRIMARY KEY (KrS));";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Table $KREISTABLE created successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }
        $this->flushBuffer();


        /* Quelle Wikipedia*/
        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (1051,'Dithmarschen','HEI, MED','SH','Heide',133210,1428.13,93),
        (1053,'Herzogtum Lauenburg','RZ','SH','Ratzeburg',197264,1263.01,156),
        (1054,'Nordfriesland','NF','SH','Husum',165507,2082.96,79),
        (1055,'Ostholstein','OH','SH','Eutin',200581,1392.55,144),
        (1056,'Pinneberg','PI','SH','Elmshorn[FN 3]',314391,664.28,473),
        (1057,'Plön','PLÖ','SH','Plön',128647,1083.17,119),
        (1058,'Rendsburg-Eckernförde','RD, ECK','SH','Rendsburg',272775,2189.17,125),
        (1059,'Schleswig-Flensburg','SL','SH','Schleswig',200025,2071.14,97),
        (1060,'Segeberg','SE','SH','Bad Segeberg',276032,1344.39,205),
        (1061,'Steinburg','IZ','SH','Itzehoe',131347,1056.13,124),
        (1062,'Stormarn','OD','SH','Bad Oldesloe',243196,766.33,317)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }

        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
       (3151,'Gifhorn','GF','NI','Gifhorn',175920,1562.86,113),
        (3153,'Goslar','GS, BRL, CLZ','NI','Goslar',137014,965.29,142),
        (3154,'Helmstedt','HE','NI','Helmstedt',91307,674.02,135),
        (3155,'Northeim','NOM, EIN, GAN','NI','Northeim',132765,1267.08,105),
        (3157,'Peine','PE','NI','Peine',133965,534.97,250),
        (3158,'Wolfenbüttel','WF','NI','Wolfenbüttel',119960,722.56,166),
        (3159,'Göttingen','GÖ, DUD, HMÜ, OHA','NI','Göttingen',328074,1753.41,187),
        (3241,'Hannover, Region[FN 1]','H','NI','Hannover',1157624,2290.86,505),
        (3251,'Diepholz','DH, SY','NI','Diepholz',216886,1988.14,109),
        (3252,'Hameln-Pyrmont','HM','NI','Hameln',148559,796.15,187),
        (3254,'Hildesheim','HI, ALF','NI','Hildesheim',276594,1206.03,229),
        (3255,'Holzminden','HOL','NI','Holzminden',70975,692.65,102),
        (3256,'Nienburg/Weser','NI','NI','Nienburg/Weser',121386,1398.97,87),
        (3257,'Schaumburg','SHG, RI','NI','Stadthagen',157781,675.57,234),
        (3351,'Celle','CE','NI','Celle',178936,1545.21,116),
        (3352,'Cuxhaven','CUX','NI','Cuxhaven',198213,2057.78,96),
        (3353,'Harburg','WL','NI','Winsen (Luhe)',252776,1245.03,203),
        (3354,'Lüchow-Dannenberg','DAN','NI','Lüchow (Wendland)',48424,1220.75,40),
        (3355,'Lüneburg','LG','NI','Lüneburg',183372,1323.68,139),
        (3356,'Osterholz','OHZ','NI','Osterholz-Scharmbeck',113517,650.81,174),
        (3357,'Rotenburg (Wümme)','ROW, BRV','NI','Rotenburg (Wümme)',163455,2070.45,79),
        (3358,'Heidekreis','HK','NI','Bad Fallingbostel',139755,1873.72,75),
        (3359,'Stade','STD','NI','Stade',203102,1266.02,160),
        (3360,'Uelzen','UE','NI','Uelzen',92572,1454.22,64),
        (3361,'Verden','VER','NI','Verden (Aller)',136792,787.97,174),
        (3451,'Ammerland','WST','NI','Westerstede',124071,728.38,170),
        (3452,'Aurich','AUR, NOR','NI','Aurich',189848,1287.31,147),
        (3453,'Cloppenburg','CLP','NI','Cloppenburg',169348,1418.45,119),
        (3454,'Emsland','EL','NI','Meppen',325657,2882.07,113),
        (3455,'Friesland','FRI','NI','Jever',98460,607.91,162),
        (3456,'Grafschaft Bentheim','NOH','NI','Nordhorn',136511,980.87,139),
        (3457,'Leer','LER','NI','Leer (Ostfriesland)',169809,1086.01,156),
        (3458,'Oldenburg','OL','NI','Wildeshausen',130144,1063.16,122),
        (3459,'Osnabrück','OS, BSB, MEL, WTL','NI','Osnabrück[FN 2]',357343,2121.63,168),
        (3460,'Vechta','VEC','NI','Vechta',141598,812.63,174),
        (3461,'Wesermarsch','BRA','NI','Brake (Unterweser)',88624,822.01,108),
        (3462,'Wittmund','WTM','NI','Wittmund',56882,656.64,87)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }

        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (5154,'Kleve','KLE, GEL','NW','Kleve',310974,1232.99,252),
        (5158,'Mettmann','ME','NW','Mettmann',485684,407.22,1193),
        (5162,'Rhein-Kreis Neuss','NE, GV','NW','Neuss',451007,576.52,782),
        (5166,'Viersen','VIE, KK','NW','Viersen',298935,563.28,531),
        (5170,'Wesel','WES, DIN, MO','NW','Wesel',459809,1042.8,441),
        (5334,'Aachen, Städteregion[FN 1]','AC, MON','NW','Aachen',555465,706.95,786),
        (5358,'Düren','DN, JÜL, MON, SLE','NW','Düren',263722,941.37,280),
        (5362,'Rhein-Erft-Kreis','BM','NW','Bergheim',470089,704.71,667),
        (5366,'Euskirchen','EU, SLE','NW','Euskirchen',192840,1248.73,154),
        (5370,'Heinsberg','HS, ERK, GK','NW','Heinsberg',254322,627.99,405),
        (5374,'Oberbergischer Kreis','GM','NW','Gummersbach',272471,918.84,297),
        (5378,'Rheinisch-Bergischer Kreis','GL','NW','Bergisch Gladbach',283455,437.32,648),
        (5382,'Rhein-Sieg-Kreis','SU','NW','Siegburg',599780,1153.21,520),
        (5554,'Borken','BOR, AH, BOH','NW','Borken',370676,1420.98,261),
        (5558,'Coesfeld','COE, LH','NW','Coesfeld',219929,1112.04,198),
        (5562,'Recklinghausen','RE, CAS, GLA','NW','Recklinghausen',615261,761.31,808),
        (5566,'Steinfurt','ST, BF, TE','NW','Steinfurt',447614,1795.76,249),
        (5570,'Warendorf','WAF, BE','NW','Warendorf',277783,1319.41,211),
        (5754,'Gütersloh','GT','NW','Gütersloh',364083,969.21,376),
        (5758,'Herford','HF','NW','Herford',250783,450.41,557),
        (5762,'Höxter','HX, WAR','NW','Höxter',140667,1201.42,117),
        (5766,'Lippe','LIP','NW','Detmold',348391,1246.21,280),
        (5770,'Minden-Lübbecke','MI','NW','Minden',310710,1152.41,270),
        (5774,'Paderborn','PB, BÜR','NW','Paderborn',306890,1246.8,246),
        (5954,'Ennepe-Ruhr-Kreis','EN, WIT','NW','Schwelm',324296,409.64,792),
        (5958,'Hochsauerlandkreis','HSK','NW','Meschede',260475,1960.17,133),
        (5962,'Märkischer Kreis','MK','NW','Lüdenscheid',412120,1061.06,388),
        (5966,'Olpe','OE','NW','Olpe',134775,712.14,189),
        (5970,'Siegen-Wittgenstein','SI, BLB','NW','Siegen',278210,1132.89,246),
        (5974,'Soest','SO, LP','NW','Soest',301902,1328.63,227),
        (5978,'Unna','UN, LH, LÜN','NW','Unna',394782,543.21,727)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }

        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (6431,'Bergstraße','HP','HE','Heppenheim',269694,719.52,375),
        (6432,'Darmstadt-Dieburg','DA, DI','HE','Darmstadt[FN 2]',297399,658.65,452),
        (6433,'Groß-Gerau','GG','HE','Groß-Gerau',274526,453.04,606),
        (6434,'Hochtaunuskreis','HG, USI','HE','Bad Homburg vor der Höhe',236564,482.02,491),
        (6435,'Main-Kinzig-Kreis','MKK, HU, GN, SLÜ','HE','Gelnhausen',418950,1397.55,300),
        (6436,'Main-Taunus-Kreis','MTK','HE','Hofheim am Taunus',237735,222.39,1069),
        (6437,'Odenwaldkreis','ERB','HE','Erbach',96798,623.98,155),
        (6438,'Offenbach','OF','HE','Dietzenbach',354092,356.3,994),
        (6439,'Rheingau-Taunus-Kreis','RÜD, SWA','HE','Bad Schwalbach',187157,811.48,231),
        (6440,'Wetteraukreis','FB, BÜD','HE','Friedberg (Hessen)',306460,1100.69,278),
        (6531,'Gießen','GI','HE','Gießen',268876,854.67,315),
        (6532,'Lahn-Dill-Kreis','LDK, DIL, (WZ)','HE','Wetzlar',253777,1066.52,238),
        (6533,'Limburg-Weilburg','LM, WEL','HE','Limburg an der Lahn',172083,738.48,233),
        (6534,'Marburg-Biedenkopf','MR, BID','HE','Marburg',246648,1262.55,195),
        (6535,'Vogelsbergkreis','VB','HE','Lauterbach (Hessen)',105878,1458.99,73),
        (6631,'Fulda','FD','HE','Fulda',222584,1380.4,161),
        (6632,'Hersfeld-Rotenburg','HEF, ROF','HE','Bad Hersfeld',120829,1097.12,110),
        (6633,'Kassel','KS, HOG, WOH','HE','Kassel[FN 2]',236633,1292.92,183),
        (6634,'Schwalm-Eder-Kreis','HR, FZ, MEG, ZIG','HE','Homberg (Efze)',180222,1538.51,117),
        (6635,'Waldeck-Frankenberg','KB, FKB, WA','HE','Korbach',156953,1848.44,85),
        (6636,'Werra-Meißner-Kreis','ESW, WIZ','HE','Eschwege',101017,1024.7,99)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }

        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (7131,'Ahrweiler','AW','RP','Bad Neuenahr-Ahrweiler',129727,786.98,165),
        (7132,'Altenkirchen (Westerwald)','AK','RP','Altenkirchen (Westerwald)',128705,641.99,200),
        (7133,'Bad Kreuznach','KH','RP','Bad Kreuznach',158080,863.76,183),
        (7134,'Birkenfeld','BIR','RP','Birkenfeld',80720,776.58,104),
        (7135,'Cochem-Zell','COC, ZEL','RP','Cochem',61587,692.33,89),
        (7137,'Mayen-Koblenz','MYK, MY','RP','Koblenz[FN 2]',214259,817.25,262),
        (7138,'Neuwied','NR','RP','Neuwied',181941,626.9,290),
        (7140,'Rhein-Hunsrück-Kreis','SIM, GOA','RP','Simmern/Hunsrück',102937,991.12,104),
        (7141,'Rhein-Lahn-Kreis','EMS, DIZ, GOH','RP','Bad Ems',122308,782.29,156),
        (7143,'Westerwaldkreis','WW','RP','Montabaur',201597,988.95,204),
        (7231,'Bernkastel-Wittlich','WIL, BKS','RP','Wittlich',112262,1167.56,96),
        (7232,'Eifelkreis Bitburg-Prüm','BIT, PRÜ','RP','Bitburg',98561,1626.22,61),
        (7233,'Vulkaneifel','DAU','RP','Daun',60603,911.02,67),
        (7235,'Trier-Saarburg','TR, SAB','RP','Trier[FN 2]',148945,1101.49,135),
        (7331,'Alzey-Worms','AZ','RP','Alzey',129244,588.15,220),
        (7332,'Bad Dürkheim','DÜW','RP','Bad Dürkheim',132660,594.76,223),
        (7333,'Donnersbergkreis','KIB, ROK','RP','Kirchheimbolanden',75101,645.52,116),
        (7334,'Germersheim','GER','RP','Germersheim',129075,463.35,279),
        (7335,'Kaiserslautern','KL','RP','Kaiserslautern[FN 2]',106057,639.88,166),
        (7336,'Kusel','KUS','RP','Kusel',70526,573.28,123),
        (7337,'Südliche Weinstraße','SÜW','RP','Landau in der Pfalz[FN 2]',110356,639.83,172),
        (7338,'Rhein-Pfalz-Kreis','RP','RP','Ludwigshafen am Rhein[FN 2]',154201,304.92,506),
        (7339,'Mainz-Bingen','MZ, BIN','RP','Ingelheim am Rhein',210889,605.77,348),
        (7340,'Südwestpfalz','PS, ZW','RP','Pirmasens[FN 2]',95113,953.65,100)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }

        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (8115,'Böblingen','BB, LEO','BW','Böblingen',391640,617.82,634),
        (8116,'Esslingen','ES, NT','BW','Esslingen am Neckar',533859,641.46,832),
        (8117,'Göppingen','GP','BW','Göppingen',257253,642.36,400),
        (8118,'Ludwigsburg','LB, VAI','BW','Ludwigsburg',543984,686.84,792),
        (8119,'Rems-Murr-Kreis','WN, BK','BW','Waiblingen',426158,858.13,497),
        (8125,'Heilbronn','HN','BW','Heilbronn[FN 2]',343068,1099.9,312),
        (8126,'Hohenlohekreis','KÜN, ÖHR','BW','Künzelsau',112010,776.78,144),
        (8127,'Schwäbisch Hall','SHA, BK, CR','BW','Schwäbisch Hall',195861,1483.97,132),
        (8128,'Main-Tauber-Kreis','TBB, MGH','BW','Tauberbischofsheim',132321,1304.4,101),
        (8135,'Heidenheim','HDH','BW','Heidenheim an der Brenz',132472,627.14,211),
        (8136,'Ostalbkreis','AA, GD','BW','Aalen',314002,1511.54,208),
        (8215,'Karlsruhe','KA','BW','Karlsruhe[FN 2]',444232,1084.96,409),
        (8216,'Rastatt','RA, BH','BW','Rastatt',231018,738.75,313),
        (8225,'Neckar-Odenwald-Kreis','MOS, BCH','BW','Mosbach',143535,1126.24,127),
        (8226,'Rhein-Neckar-Kreis','HD','BW','Heidelberg[FN 2]',547625,1061.7,516),
        (8235,'Calw','CW','BW','Calw',158397,797.53,199),
        (8236,'Enzkreis','PF','BW','Pforzheim[FN 2]',198905,573.68,347),
        (8237,'Freudenstadt','FDS, HCH, HOR, WOL','BW','Freudenstadt',117935,870.67,135),
        (8315,'Breisgau-Hochschwarzwald','FR','BW','Freiburg im Breisgau[FN 2]',262795,1378.34,191),
        (8316,'Emmendingen','EM','BW','Emmendingen',165383,679.9,243),
        (8317,'Ortenaukreis','OG, BH, KEL, LR, WOL','BW','Offenburg',429479,1860.81,231),
        (8325,'Rottweil','RW','BW','Rottweil',139455,769.4,181),
        (8326,'Schwarzwald-Baar-Kreis','VS','BW','Villingen-Schwenningen',212381,1025.27,207),
        (8327,'Tuttlingen','TUT','BW','Tuttlingen',140152,734.35,191),
        (8335,'Konstanz','KN, (BÜS)','BW','Konstanz',285325,817.97,349),
        (8336,'Lörrach','LÖ','BW','Lörrach',228639,806.76,283),
        (8337,'Waldshut','WT','BW','Waldshut-Tiengen',170619,1131.15,151),
        (8415,'Reutlingen','RT','BW','Reutlingen',286748,1092.74,262),
        (8416,'Tübingen','TÜ','BW','Tübingen',227331,519.2,438),
        (8417,'Zollernalbkreis','BL, HCH','BW','Balingen',188935,917.72,206),
        (8425,'Alb-Donau-Kreis','UL','BW','Ulm[FN 2]',196047,1358.68,144),
        (8426,'Biberach','BC','BW','Biberach an der Riß',199742,1409.74,142),
        (8435,'Bodenseekreis','FN, TT, ÜB','BW','Friedrichshafen',216227,664.81,325),
        (8436,'Ravensburg','RV','BW','Ravensburg',284285,1631.86,174),
        (8437,'Sigmaringen','SIG','BW','Sigmaringen',130873,1204.34,109)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }

        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (9171,'Altötting','AÖ, LF','BY','Altötting',111210,569.35,195),
        (9172,'Berchtesgadener Land','BGL, BGD, LF, REI','BY','Bad Reichenhall',105722,839.92,126),
        (9173,'Bad Tölz-Wolfratshausen','TÖL, WOR','BY','Bad Tölz',127227,1110.69,115),
        (9174,'Dachau','DAH','BY','Dachau',153884,579.18,266),
        (9175,'Ebersberg','EBE','BY','Ebersberg',142142,549.36,259),
        (9176,'Eichstätt','EI','BY','Eichstätt',132341,1214.06,109),
        (9177,'Erding','ED','BY','Erding',137660,870.72,158),
        (9178,'Freising','FS','BY','Freising',179116,799.83,224),
        (9179,'Fürstenfeldbruck','FFB','BY','Fürstenfeldbruck',219320,434.79,504),
        (9180,'Garmisch-Partenkirchen','GAP','BY','Garmisch-Partenkirchen',88467,1012.22,87),
        (9181,'Landsberg am Lech','LL','BY','Landsberg am Lech',120071,804.38,149),
        (9182,'Miesbach','MB','BY','Miesbach',99726,866.23,115),
        (9183,'Mühldorf am Inn','MÜ, VIB, WS','BY','Mühldorf am Inn',115250,805.31,143),
        (9184,'München','M, AIB, WOR','BY','München[FN 2]',348871,664.25,525),
        (9185,'Neuburg-Schrobenhausen','ND, SOB','BY','Neuburg an der Donau',96680,739.81,131),
        (9186,'Pfaffenhofen an der Ilm','PAF','BY','Pfaffenhofen an der Ilm',127151,761.14,167),
        (9187,'Rosenheim','RO, AIB, WS','BY','Rosenheim[FN 2]',260983,1439.54,181),
        (9188,'Starnberg','STA, WOR','BY','Starnberg',136092,487.71,279),
        (9189,'Traunstein','TS, LF','BY','Traunstein',177089,1534,115),
        (9190,'Weilheim-Schongau','WM, SOG','BY','Weilheim in Oberbayern',135348,966.37,140),
        (9271,'Deggendorf','DEG','BY','Deggendorf',119326,861.3,139),
        (9272,'Freyung-Grafenau','FRG, GRA, WOS','BY','Freyung',78355,984.15,80),
        (9273,'Kelheim','KEH, MAI, PAR, RID, ROL','BY','Kelheim',122258,1065.97,115),
        (9274,'Landshut','LA, MAI, MAL, ROL, VIB','BY','Landshut[FN 2]',158698,1347.89,118),
        (9275,'Passau','PA','BY','Passau[FN 2]',192043,1530.29,125),
        (9276,'Regen','REG, VIT','BY','Regen',77656,974.92,80),
        (9277,'Rottal-Inn','PAN, EG, GRI, VIB','BY','Pfarrkirchen',120659,1281.42,94),
        (9278,'Straubing-Bogen','SR, BOG, MAL','BY','Straubing[FN 2]',100649,1201.94,84),
        (9279,'Dingolfing-Landau','DGF, LAN','BY','Dingolfing',96217,877.79,110),
        (9371,'Amberg-Sulzbach','AS, BUL, ESB, NAB, SUL','BY','Amberg[FN 2]',103109,1255.75,82),
        (9372,'Cham','CHA, KÖZ, ROD, WÜM','BY','Cham',127882,1520.17,84),
        (9373,'Neumarkt in der Oberpfalz','NM, PAR','BY','Neumarkt in der Oberpfalz',133561,1344.11,99),
        (9374,'Neustadt an der Waldnaab','NEW, ESB, VOH','BY','Neustadt an der Waldnaab',94352,1427.67,66),
        (9375,'Regensburg','R','BY','Regensburg[FN 2]',193572,1391.9,139),
        (9376,'Schwandorf','SAD, BUL, NAB, NEN, OVI, ROD','BY','Schwandorf',147189,1464.97,100),
        (9377,'Tirschenreuth','TIR, KEM','BY','Tirschenreuth',72504,1084.23,67),
        (9471,'Bamberg','BA','BY','Bamberg[FN 2]',147086,1167.83,126),
        (9472,'Bayreuth','BT, EBS, ESB, KEM, MÜB, PEG','BY','Bayreuth[FN 2]',103656,1273.74,81),
        (9473,'Coburg','CO, NEC','BY','Coburg[FN 2]',86906,590.47,147),
        (9474,'Forchheim','FO, EBS, PEG','BY','Forchheim',116099,642.79,181),
        (9475,'Hof','HO, MÜB, NAI, REH, SAN','BY','Hof[FN 2]',95311,892.52,107),
        (9476,'Kronach','KC, SAN','BY','Kronach',67135,651.53,103),
        (9477,'Kulmbach','KU, EBS, SAN','BY','Kulmbach',71845,658.34,109),
        (9478,'Lichtenfels','LIF, STE','BY','Lichtenfels',66838,519.95,129),
        (9479,'Wunsiedel im Fichtelgebirge','WUN, MAK, REH, SEL','BY','Wunsiedel',73178,606.43,121),
        (9571,'Ansbach','AN, DKB, FEU, ROT','BY','Ansbach[FN 2]',183949,1971.84,93),
        (9572,'Erlangen-Höchstadt','ERH, HÖS','BY','Erlangen[FN 2]',136271,564.66,241),
        (9573,'Fürth','FÜ','BY','Zirndorf',117387,307.55,382),
        (9574,'Nürnberger Land','LAU, ESB, HEB, N, PEG','BY','Lauf an der Pegnitz',170365,799.57,213),
        (9575,'Neustadt an der Aisch-Bad Windsheim','NEA, SEF, UFF','BY','Neustadt an der Aisch',100364,1267.56,79),
        (9576,'Roth','RH, HIP','BY','Roth',126958,895.39,142),
        (9577,'Weißenburg-Gunzenhausen','WUG, GUN','BY','Weißenburg in Bayern',94393,970.91,97),
        (9671,'Aschaffenburg','AB, ALZ','BY','Aschaffenburg[FN 2]',174208,699.15,249),
        (9672,'Bad Kissingen','KG, BRK, HAB','BY','Bad Kissingen',103218,1136.96,91),
        (9673,'Rhön-Grabfeld','NES, KÖN, MET','BY','Bad Neustadt an der Saale',79690,1021.77,78),
        (9674,'Haßberge','HAS, EBN, GEO, HOH','BY','Haßfurt',84599,956.38,88),
        (9675,'Kitzingen','KT','BY','Kitzingen',90909,684.19,133),
        (9676,'Miltenberg','MIL, OBB','BY','Miltenberg',128756,715.86,180),
        (9677,'Main-Spessart','MSP','BY','Karlstadt',126365,1321.42,96),
        (9678,'Schweinfurt','SW, GEO','BY','Schweinfurt[FN 2]',115106,841.46,137),
        (9679,'Würzburg','WÜ, OCH','BY','Würzburg[FN 2]',161834,968.4,167),
        (9771,'Aichach-Friedberg','AIC, FDB','BY','Aichach',133596,780.33,171),
        (9772,'Augsburg','A, SMÜ, WER','BY','Augsburg[FN 2]',251534,1071.13,235),
        (9773,'Dillingen an der Donau','DLG, WER','BY','Dillingen an der Donau',96021,792.22,121),
        (9774,'Günzburg','GZ, KRU','BY','Günzburg',125747,762.44,165),
        (9775,'Neu-Ulm','NU, ILL','BY','Neu-Ulm',174200,515.86,338),
        (9776,'Lindau (Bodensee)','LI','BY','Lindau (Bodensee)',81669,323.44,252),
        (9777,'Ostallgäu','OAL, FÜS, MOD','BY','Marktoberdorf',140316,1394.91,101),
        (9778,'Unterallgäu','MN','BY','Mindelheim',144041,1230.06,117),
        (9779,'Donau-Ries','DON, NÖ','BY','Donauwörth',133496,1274.68,105),
        (9780,'Oberallgäu','OA','BY','Sonthofen',155362,1528,102)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }

        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (10041,'Saarbrücken, Regionalverband[FN 1]','SB, (VK)','SL','Saarbrücken',329708,410.64,803),
        (10042,'Merzig-Wadern','MZG','SL','Merzig',103366,555.17,186),
        (10043,'Neunkirchen','NK','SL','Ottweiler',132206,249.24,530),
        (10044,'Saarlouis','SLS','SL','Saarlouis',195201,459.05,425),
        (10045,'Saarpfalz-Kreis','HOM, (IGB)','SL','Homburg',142631,418.4,341),
        (10046,'St. Wendel','WND','SL','St. Wendel',87397,476.22,184)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }

        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (12060,'Barnim','BAR, BER, EW','BB','Eberswalde',182760,1479.67,124),
        (12061,'Dahme-Spreewald','LDS, KW, LC, LN','BB','Lübben (Spreewald)',169067,2274.48,74),
        (12062,'Elbe-Elster','EE, FI, LIB','BB','Herzberg (Elster)',102638,1899.54,54),
        (12063,'Havelland','HVL, NAU, RN','BB','Rathenow',161909,1727.3,94),
        (12064,'Märkisch-Oderland','MOL, FRW, SEE, SRB','BB','Seelow',194328,2158.67,90),
        (12065,'Oberhavel','OHV','BB','Oranienburg',211249,1808.18,117),
        (12066,'Oberspreewald-Lausitz','OSL, CA, SFB','BB','Senftenberg',110476,1223.08,90),
        (12067,'Oder-Spree','LOS, BSK, EH, FW','BB','Beeskow',178658,2256.78,79),
        (12068,'Ostprignitz-Ruppin','OPR, KY, NP, WK','BB','Neuruppin',99078,2526.55,39),
        (12069,'Potsdam-Mittelmark','PM','BB','Bad Belzig',214664,2591.61,83),
        (12070,'Prignitz','PR','BB','Perleberg',76508,2138.61,36),
        (12071,'Spree-Neiße','SPN, FOR, GUB, SPB','BB','Forst (Lausitz)',114429,1657.45,69),
        (12072,'Teltow-Fläming','TF','BB','Luckenwalde',168296,2104.19,80),
        (12073,'Uckermark','UM, ANG, PZ, SDT, TP','BB','Prenzlau',119552,3076.93,39)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }

        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (13071,'Mecklenburgische Seenplatte','MSE, AT, DM, MC, MST, MÜR, NZ, RM, WRN, (NB)','MV','Neubrandenburg',259130,5470.35,47),
        (13072,'Rostock','LRO, BÜZ, DBR, GÜ, ROS, TET','MV','Güstrow',215113,3422.51,63),
        (13073,'Vorpommern-Rügen','VR, GMN, NVP, RDG, RÜG, (HST)','MV','Stralsund',224684,3207.37,70),
        (13074,'Nordwestmecklenburg','NWM, GDB, GVM, WIS, (HWI)','MV','Wismar',156729,2118.51,74),
        (13075,'Vorpommern-Greifswald','VG, ANK, GW, PW, SBG, UEM, WLG, (HGW)','MV','Greifswald',236697,3929.73,60),
        (13076,'Ludwigslust-Parchim','LUP, HGN, LBZ, LWL, PCH, STB','MV','Parchim',212618,4752.44,45)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }

        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (14521,'Erzgebirgskreis','ERZ, ANA, ASZ, AU, MAB, MEK, STL, SZB, ZP','SN','Annaberg-Buchholz',337696,1827.91,185),
        (14522,'Mittelsachsen','FG, BED, DL, FLÖ, HC, MW, RL','SN','Freiberg',306185,2116.85,145),
        (14523,'Vogtlandkreis','V, AE, OVL, PL, RC','SN','Plauen',227796,1412.42,161),
        (14524,'Zwickau','Z, GC, HOT, WDA','SN','Zwickau',317531,949.78,334),
        (14625,'Bautzen','BZ, BIW, HY, KM','SN','Bautzen',300880,2395.61,126),
        (14626,'Görlitz','GR, LÖB, NOL, NY, WSW, ZI','SN','Görlitz',254894,2111.41,121),
        (14627,'Meißen','MEI, GRH, RG, RIE','SN','Meißen',242165,1454.59,166),
        (14628,'Sächsische Schweiz-Osterzgebirge','PIR, DW, FTL, SEB','SN','Pirna',245611,1654.19,148),
        (14729,'Leipzig','L, BNA, GHA, GRM, MTL, WUR','SN','Borna',257763,1651.3,156),
        (14730,'Nordsachsen','TDO, DZ, EB, OZ, TG, TO','SN','Torgau',197673,2028.56,97)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }

        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (15081,'Altmarkkreis Salzwedel','SAW, GA, KLZ','ST','Salzwedel',83765,2293.03,37),
        (15082,'Anhalt-Bitterfeld','ABI, AZE, BTF, KÖT, ZE','ST','Köthen',159854,1453.52,110),
        (15083,'Börde','BK, BÖ, HDL, OC, OK, WMS, WZL','ST','Haldensleben',171734,2366.63,73),
        (15084,'Burgenlandkreis','BLK, HHM, NEB, NMB, WSF, ZZ','ST','Naumburg (Saale)',180190,1413.67,127),
        (15085,'Harz','HZ, HBS, QLB, WR','ST','Halberstadt',214446,2104.54,102),
        (15086,'Jerichower Land','JL, BRG, GNT','ST','Burg',89928,1576.84,57),
        (15087,'Mansfeld-Südharz','MSH, EIL, HET, ML, SGH','ST','Sangerhausen',136249,1448.84,94),
        (15088,'Saalekreis','SK, MER, MQ, QFT','ST','Merseburg',184582,1433.67,129),
        (15089,'Salzlandkreis','SLK, ASL, BBG, SBK, SFT','ST','Bernburg (Saale)',190560,1426.76,134),
        (15090,'Stendal','SDL, HV, OBG','ST','Stendal',111982,2423.16,46),
        (15091,'Wittenberg','WB, GHC, JE','ST','Lutherstadt Wittenberg',125840,1930.31,65)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }

        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (16061,'Eichsfeld','EIC, HIG, WBS','TH','Heilbad Heiligenstadt',100380,943.07,106),
        (16062,'Nordhausen','NDH','TH','Nordhausen',83822,713.9,117),
        (16063,'Wartburgkreis','WAK, SLZ','TH','Bad Salzungen',119726,1267.26,94),
        (16064,'Unstrut-Hainich-Kreis','UH, LSZ, MHL','TH','Mühlhausen/Thüringen',102912,979.69,105),
        (16065,'Kyffhäuserkreis','KYF, ART, SDH','TH','Sondershausen',75009,1037.91,72),
        (16066,'Schmalkalden-Meiningen','SM, MGN','TH','Meiningen',125646,1251.2,100),
        (16067,'Gotha','GTH','TH','Gotha',135452,936.08,145),
        (16068,'Sömmerda','SÖM','TH','Sömmerda',69655,806.86,86),
        (16069,'Hildburghausen','HBN','TH','Hildburghausen',63553,938.42,68),
        (16070,'Ilm-Kreis','IK, ARN, IL','TH','Arnstadt',106622,805.11,132),
        (16071,'Weimarer Land','AP, APD','TH','Apolda',81947,804.48,102),
        (16072,'Sonneberg','SON, NH','TH','Sonneberg',58410,433.61,135),
        (16073,'Saalfeld-Rudolstadt','SLF, RU','TH','Saalfeld/Saale',104142,1036.03,101),
        (16074,'Saale-Holzland-Kreis','SHK, EIS, SRO','TH','Eisenberg',83051,815.24,102),
        (16075,'Saale-Orla-Kreis','SOK, LBS, PN, SCZ','TH','Schleiz',80868,1151.3,70),
        (16076,'Greiz','GRZ, ZR','TH','Greiz',98159,845.97,116),
        (16077,'Altenburger Land','ABG, SLN','TH','Altenburg',90118,569.41,158)";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Landkreise successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }


        /**Kreisfreie Städe 
         * KrS	Landkreis/Kreis	UZ	BL	Kreissitz	Einw.	Fl.in km²	Bev.D.Ew./km²
         * 
         * Fehlende KReise: SELECT DISTINCT IdLandkreis, Landkreis,Bundesland FROM CORONA.RKI WHERE IdLandkreis NOT IN (SELECT Krs FROM CORONA.Kreise)
         */
        $sql = "INSERT INTO " . $KREISTABLE . " VALUES
        (1001,'SK Flensburg','','SH','',89504,56.73,0),
        (1002,'SK Kiel','','SH','',247548,118.65,0),
        (1003,'SK Lübeck','','SH','',217198 ,214.19,0),
        (1004,'SK Neumünster','','SH','',79487,71.66,0),
        (2000,'SK Hamburg','','HH','',1841179,755.09,0),
        (3101,'SK Braunschweig','','NI','',248292,192.70,0),
        (3102,'SK Salzgitter','','NI','',0,0.0,0),
        (3103,'SK Wolfsburg','','NI','',0,0.0,0),
        (3401,'SK Delmenhorst','','NI','',0,0.0,0),
        (3402,'SK Emden','','NI','',0,0.0,0),
        (3403,'SK Oldenburg','','NI','',0,0.0,0),
        (3404,'SK Osnabrück','','NI','',0,0.0,0),
        (3405,'SK Wilhelmshaven','','NI','',0,0.0,0),
        (4011,'SK Bremen','','HB','',0,0.0,0),
        (4012,'SK Bremerhaven','','HB','',0,0.0,0),
        (5111,'SK Düsseldorf','','NW','',0,0.0,0),
        (5112,'SK Duisburg','','NW','',0,0.0,0),
        (5113,'SK Essen','','NW','',0,0.0,0),
        (5114,'SK Krefeld','','NW','',0,0.0,0),
        (5116,'SK Mönchengladbach','','NW','',0,0.0,0),
        (5117,'SK Mülheim a.d.Ruhr','','NW','',0,0.0,0),
        (5119,'SK Oberhausen','','NW','',0,0.0,0),
        (5120,'SK Remscheid','','NW','',0,0.0,0),
        (5122,'SK Solingen','','NW','',0,0.0,0),
        (5124,'SK Wuppertal','','NW','',0,0.0,0),
        (5314,'SK Bonn','','NW','',0,0.0,0),
        (5315,'SK Köln','','NW','',0,0.0,0),
        (5316,'SK Leverkusen','','NW','',0,0.0,0),
        (5512,'SK Bottrop','','NW','',0,0.0,0),
        (5513,'SK Gelsenkirchen','','NW','',0,0.0,0),
        (5515,'SK Münster','','NW','',0,0.0,0),
        (5711,'SK Bielefeld','','NW','',0,0.0,0),
        (5911,'SK Bochum','','NW','',0,0.0,0),
        (5913,'SK Dortmund','','NW','',0,0.0,0),
        (5914,'SK Hagen','','NW','',0,0.0,0),
        (5915,'SK Hamm','','NW','',0,0.0,0),
        (5916,'SK Herne','','NW','',0,0.0,0),
        (6411,'SK Darmstadt','','HE','',0,0.0,0),
        (6412,'SK Frankfurt am Main','','HE','',0,0.0,0),
        (6413,'SK Offenbach','','HE','',0,0.0,0),
        (6414,'SK Wiesbaden','','HE','',0,0.0,0),
        (6611,'SK Kassel','','HE','',0,0.0,0),
        (7111,'SK Koblenz','','RP','',0,0.0,0),
        (7211,'SK Trier','','RP','',0,0.0,0),
        (7311,'SK Frankenthal','','RP','',0,0.0,0),
        (7312,'SK Kaiserslautern','','RP','',0,0.0,0),
        (7313,'SK Landau i.d.Pfalz','','RP','',0,0.0,0),
        (7314,'SK Ludwigshafen','','RP','',0,0.0,0),
        (7315,'SK Mainz','','RP','',0,0.0,0),
        (7316,'SK Neustadt a.d.Weinstraße','','RP','',0,0.0,0),
        (7317,'SK Pirmasens','','RP','',0,0.0,0),
        (7318,'SK Speyer','','RP','',0,0.0,0),
        (7319,'SK Worms','','RP','',0,0.0,0),
        (7320,'SK Zweibrücken','','RP','',0,0.0,0),
        (8111,'SK Stuttgart','','BW','',0,0.0,0),
        (8121,'SK Heilbronn','','BW','',0,0.0,0),
        (8211,'SK Baden-Baden','','BW','',0,0.0,0),
        (8212,'SK Karlsruhe','','BW','',0,0.0,0),
        (8221,'SK Heidelberg','','BW','',0,0.0,0),
        (8222,'SK Mannheim','','BW','',0,0.0,0),
        (8231,'SK Pforzheim','','BW','',0,0.0,0),
        (8311,'SK Freiburg i.Breisgau','','BW','',0,0.0,0),
        (8421,'SK Ulm','','BW','',0,0.0,0),
        (9161,'SK Ingolstadt','','BY','',0,0.0,0),
        (9162,'SK München','','BY','',0,0.0,0),
        (9163,'SK Rosenheim','','BY','',0,0.0,0),
        (9261,'SK Landshut','','BY','',0,0.0,0),
        (9262,'SK Passau','','BY','',0,0.0,0),
        (9263,'SK Straubing','','BY','',0,0.0,0),
        (9361,'SK Amberg','','BY','',0,0.0,0),
        (9362,'SK Regensburg','','BY','',0,0.0,0),
        (9363,'SK Weiden i.d.OPf.','','BY','',0,0.0,0),
        (9461,'SK Bamberg','','BY','',0,0.0,0),
        (9462,'SK Bayreuth','','BY','',0,0.0,0),
        (9463,'SK Coburg','','BY','',0,0.0,0),
        (9464,'SK Hof','','BY','',0,0.0,0),
        (9561,'SK Ansbach','','BY','',0,0.0,0),
        (9562,'SK Erlangen','','BY','',0,0.0,0),
        (9563,'SK Fürth','','BY','',0,0.0,0),
        (9564,'SK Nürnberg','','BY','',0,0.0,0),
        (9565,'SK Schwabach','','BY','',0,0.0,0),
        (9661,'SK Aschaffenburg','','BY','',0,0.0,0),
        (9662,'SK Schweinfurt','','BY','',0,0.0,0),
        (9663,'SK Würzburg','','BY','',0,0.0,0),
        (9761,'SK Augsburg','','BY','',0,0.0,0),
        (9762,'SK Kaufbeuren','','BY','',0,0.0,0),
        (9763,'SK Kempten','','BY','',0,0.0,0),
        (9764,'SK Memmingen','','BY','',0,0.0,0),
        (11001,'SK Berlin Mitte','','BE','',0,0.0,0),
        (11002,'SK Berlin Friedrichshain-Kreuzbe','','BE','',0,0.0,0),
        (11003,'SK Berlin Pankow','','BE','',0,0.0,0),
        (11004,'SK Berlin Charlottenburg-Wilmers','','BE','',0,0.0,0),
        (11005,'SK Berlin Spandau','','BE','',0,0.0,0),
        (11006,'SK Berlin Steglitz-Zehlendorf','','BE','',0,0.0,0),
        (11007,'SK Berlin Tempelhof-Schöneberg','','BE','',0,0.0,0),
        (11008,'SK Berlin Neukölln','','BE','',0,0.0,0),
        (11009,'SK Berlin Treptow-Köpenick','','BE','',0,0.0,0),
        (11010,'SK Berlin Marzahn-Hellersdorf','','BE','',0,0.0,0),
        (11011,'SK Berlin Lichtenberg','','BE','',0,0.0,0),
        (11012,'SK Berlin Reinickendorf','','BE','',0,0.0,0),
        (12051,'SK Brandenburg a.d.Havel','','BB','',0,0.0,0),
        (12052,'SK Cottbus','','BB','',0,0.0,0),
        (12053,'SK Frankfurt (Oder)','','BB','',0,0.0,0),
        (12054,'SK Potsdam','','BB','',0,0.0,0),
        (13003,'SK Rostock','','MV','',0,0.0,0),
        (13004,'SK Schwerin','','MV','',0,0.0,0),
        (14511,'SK Chemnitz','','SN','',0,0.0,0),
        (14612,'SK Dresden','','SN','',0,0.0,0),
        (14713,'SK Leipzig','','SN','',0,0.0,0),
        (15001,'SK Dessau-Roßlau','','ST','',0,0.0,0),
        (15002,'SK Halle','','ST','',0,0.0,0),
        (15003,'SK Magdeburg','','ST','',0,0.0,0),
        (16051,'SK Erfurt','','TH','',0,0.0,0),
        (16052,'SK Gera','','TH','',0,0.0,0),
        (16053,'SK Jena','','TH','',0,0.0,0),
        (16054,'SK Suhl','','TH','',0,0.0,0),
        (16055,'SK Weimar','','TH','',0,0.0,0),
        (16056,'SK Eisenach','','TH','',0,0.0,0);";
        if (mysqli_query($this->link, $sql)) {
            if (DEBUG) echo "<!-- Insert Kreisfreie Städte successfully -->\n";
        } else {
            if (DEBUG) echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . " -->\n";
        }
    }
}

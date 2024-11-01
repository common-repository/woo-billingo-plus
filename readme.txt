=== Woo Billingo Plus ===
Contributors: passatgt
Tags: billingo, woocommerce, szamlazo, magyar
Requires at least: 5.1
Tested up to: 6.6.1
Stable tag: 4.7.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Billingo integráció WooCommerce-hez rengeteg extra funkcióval

== Description ==

> Ez nem a hivatalos Billingo bővítmény, viszont sokkal több funkciója van, így érdemes ezt is kipróbálnod.

> **PRO verzió**
> A bővítménynek elérhető a PRO verziója évi 30 €-ért, amelyet itt vásárolhatsz meg: [https://visztpeter.me/woo-billingo-plus](https://visztpeter.me/woo-billingo-plus)
> A licensz kulcs egy weboldalon aktiválható, 1 évig érvényes és 1 év e-mailes support is jár hozzá beállításhoz, testreszabáshoz, konfiguráláshoz.
> A vásárlással támogathatod a fejlesztést akkor is, ha esetleg a PRO verzióban elérhető funkciókra nincs is szükséged.

= Funkciók =

* **Manuális számlakészítés**
Minden rendelésnél a jobb oldalon megjelenik egy új gomb, rákattintáskor elküldi az adatokat a Billingonak és legenerálja a számlát.
* **Automata számlakészítés** _PRO_
Lehetőség van a számlát automatikusan elkészíteni a rendelés státusza alapján, akár különböző feltételekhez kötve(például fizetési mód, szállítási mód, ország, rendelés típusa stb...)
* **Számlaértesítő** _PRO_
A PRO verzióban be lehet linkelni a számlát a WooCommerce által küldött e-mailekbe, így nem fontos használni a Billingo számlaértesítőjét. Működik a WooCommerce Subscriptions bővítménnyel is. A számlát PDF-ben is tudja csatolni a WooCommerce levelekhez.
* **Csoportos műveletek** _PRO_
A rendeléskezelőben egyszerre több rendelést kiválasztva tudsz Billingo dokumentumokat létrehozni: sztornózás, számlakészítés, díjbekérő készítés stb...
* **Bankszinkron támogatás** _PRO_
Ha a Billingo felületén összepárosítás egy banki tranzakcióval egy fizetetlen számlát, akkor a WooCommerce-ben lévő számla is fizetett lesz és rendelés státuszt is tud módosítani automatán.
* **E-Nyugta** _PRO_
Ha elektronikus termékeket, jegyeket, letölthető tartalmakat értékesítesz, nem fontos bekérni a vásárló számlázási címét, elég csak az email címét, a bővítmény pedig elektronikus nyugtát készít
* **Nemzetközi számla**
Ha külföldre értékesítesz például euróban, lehetőség van a számla nyelv átállítására és külön-külön beállíthatod minden pénznemhez a számlán lévő kerekítési módot. Kompatibilis WPML-el és Polylang-al is.
* **Automata díjbekérő és előlegszámla létrehozás** _PRO_
Rendelés státuszhoz és rendelés létrehozásához is lehet társítani díjbekérő vagy előlegszámla készítést különböző feltételek szerint(például fizetési mód). Lehet manuálisan is egy-egy rendeléshez külön díjbekérőt és előlegszámlát csinálni.
* **Számla megjegyzések kezelése** _PRO_
Létrehozhatsz több megjegyzést is, amit feltételekhez kötve megjeleníthetsz a számlán. Például angol nyelvű számla esetén angol megjegyzést, vagy ha egy bizonyos kategóriából van egy termék a számlán, akkor annak megfelelő megjegyzés fog látszódni.
* **Naplózás**
Minden számlakészítésnél létrehoz egy megjegyzést a rendeléshez, hogy mikor, milyen néven készült el a számla
* **Sztornózás**
A számla sztornózható a rendelés oldalon és a számlakészítés kikapcsolható 1-1 rendeléshez
* **Adószám mező**
A WooCommerce-ben alapértelmezetten nincs adószám mező. Ezzel az opcióval bekapcsolható, hogy a számlázási adatok között megjelenjen. Az adószámot a rendszer eltárolja, a vásárlónak küldött emailben és a rendelés adatai között is megjelenik. Az adószám validálás is megoldott a NAV rendszerének segítségével.
* **Mennyiségi egység**
A tételek mellett a mennyiségi egységet is feltüntetni a számlát, amelyet a beállításokban minden termékhez külön-külön meg tudod adni és megjegyzést is tudsz megadni a tételhez
* **És még sok más**
Papír és elektronikus számla állítás, áfakulcs állítás feltételek szerint, egyedi számla megjegyzések megadása, automata sztornózás, letölthető számlák a vásárló profiljában, hibás számlakészítésről e-mailes értesítő, kupon megjelenítése külön tételként, stb...

= Fontos kiemelni =
* Ha korábban használtad a hivatalos bővítményt, migrálhatod a beállításokat és a korábban készült számlákat az új bővítménybe
* Fizetési határidő és megjegyzés írható a számlákhoz
* Kuponokkal is működik, a számlán negatív tételként vagy alapból csökkentett tétel árakkal fog megjelenni
* Szállítást és egyéb díjakat is ráírja a számlára
* A számlák elérhetők egyből a Rendelések oldalról is(táblázat utolsó oszlopa)

= Használat =
Részletes dokumentációt [itt](https://visztpeter.me/dokumentacio/) találsz.
Telepítés után a WooCommerce / Beállítások oldalon meg kell adni a Billingo API beállításaiban lévő publikus és privát kulcsokat. Ha jók a kulcsok, megjelennek a számlakészítéssel kapcsolatos egyéb beállítások. A fizetési módokat és a számlatömböt kötelező kitölteni.
Minden rendelésnél jobb oldalon megjelenik egy új doboz, ahol egy gombnyomással létre lehet hozni a számlát. Az Opciók gombbal felül lehet írni a beállításokban megadott értékeket 1-1 számlához.
Ha az automata számlakészítés be van kapcsolva, akkor a rendelés lezárásakor(beállításokban megadható, hogy melyik rendelés státusznál) automatikusan létrehozza a számlát a rendszer.
A számlakészítés kikapcsolható 1-1 rendelésnél az Opciók legördülőn belül.
Az elkészült számla a rendelés aloldalán és a rendelés listában az utolsó oszlopban található PDF ikonra kattintva megnyitható.

**FONTOS:** Mindenen esetben ellenőrizd le, hogy a számlakészítés megfelelő e és konzultálj a könyvelőddel, neki is megfelelnek e a generált számlák. Sajnos minden esetet nem tudok tesztelni, különböző áfakulcsok, termékvariációk, kuponok stb..., így mindenképp teszteld le éles használat előtt és ha valami gond van, jelezd felém és megpróbálom javítani.

= Fejlesztőknek =

A Billingo-nak küldött adatokat a számlakészítés előtt módosíthatod a következő filterekkel: `wc_billingo_plus_client`, `wc_billingo_plus_invoice`, `wc_billingo_plus_complete`. Az első paraméter a Billingonak küldött adat, a második pedig a rendelés($order). Minden esetben az éppen aktív téma functions.php fájlban történjen, hogy az esetleges plugin frissítés ne törölje ki a módosításokat!

A bővítmény naplózza a számlakészítés során előforduló hibákat. Ha a fejlesztői mód be van kapcsolva, akkor Billingo felé küldött adatokat naplózni fogja. Mindkét napló elérhető a WooCommerce / Állapot / Naplók menüpontban(válaszd ki a 'wc_billingo_plus' kezdetű logfájlt).

Lehetőség van sikeres és sikertelen számlakészítés után egyedi funkciók meghívására a bővítmény módosítása nélkül:

   <?php
   add_action('wc_billingo_plus_after_invoice_success', 'sikeres_szamlakeszites',10,2);
   function($order, $response) {
     //...
   }

   add_action('wc_billingo_plus_after_invoice_error', 'sikeres_szamlakeszites',10,2);
   function($order, $e) {
     //...
   }
   ?>

= GDPR =

A bővítmény HTTP hívásokkal kommunikál a Billingo [API rendszerével](https://billingo.readthedocs.io/). Az API hívások akkor futnak le, ha számla készül(pl rendelés létrehozásánál automatikus számlázás esetén, vagy manuális számlakészítéskor a Számlakészítés gombra nyomva).
A Billingo egy külső szolgáltatás, saját [adatvédelmi nyilatkozattal](https://www.billingo.hu/adatkezelesi-tajekoztato) és [felhasználási feltételekkel](https://www.billingo.hu/felhasznalasi-feltetelek).
This extension relies on making HTTP requests to the Billingo [API](https://billingo.readthedocs.io). API calls are made when an invoice is generated(for example on order creation in case of automatic invoicing, or when you press the create invoice button manually).
Billingo is an external service and has it's own [Terms of Service](https://www.billingo.hu/felhasznalasi-feltetelek) and [Privacy Policy](https://www.billingo.hu/adatkezelesi-tajekoztato), which you can review at those links.

== Installation ==

1. Töltsd le a bővítményt
2. Wordpress-ben bővítmények / új hozzáadása menüben fel kell tölteni
3. WooCommerce / Integráció menüpontban találhatod a beállítások, itt legalább az authentikációs kulcsokat és a számlatömb azonosítót meg kell adni
4. Ha minden jól megy, működik

== Frequently Asked Questions ==

= Mi a különbség a PRO verzió és az ingyenes között? =

A PRO verzió néhány hasznos funkciót tud, amiről [itt](https://visztpeter.me/woo-billingo-plus) olvashatsz. De a legfontosabb az automata számlakészítés. Továbbá 1 éves e-mailes support is jár hozzá.

= Hogyan lehet tesztelni a számlakészítést? =

A Billingo beállításaiban kapcsold be a Sandbox módot. Ilyenkor teszt számlákat fog létrehozni a rendszer. Fontos megjegyezni, hogy csak papíralapú számlát lehet készíteni Sandbox üzemmódban.

= Valamiért nem megy a számlakészítés, mit csináljak? =

Első körben nézd meg az Eszközök / Webhely egészség menüpontban, van e valami hibaüzenet a Woo Billingo Plus bővítménnyel kapcsolatban. Ha manuális számlakészítéskor a hibaüzenetből nem derül ki, mi a gond, akkor nézd meg a WooCommerce / Állapot / Naplók menüpontban lévő hibanaplóban a teljes üzenetet. Ha ez sem segít, nyugodtan lépj kapcsolatba velem.

== Screenshots ==

1. Beállítások képernyő(WooCommerce / Beállítások / Integráció)
2. Számlakészítés doboz a rendelés oldalon

== Changelog ==

= 4.7.5 =
* HuCommerce adószám kompatibilitás javítás
* Kompatibilitás megjelölése legújabb WP/WC verzióval

= 4.7.4 =
* Deposits & Partial Payments for WooCommerce kompatibilitás(lehet számlát generálni az előleghez és a fennmaradó összeghez is)
* wc_billingo_plus_ipn_should_change_order_status filter, amivel állítható, hogy mikor váltson státusz a bankszinkron után

= 4.7.3 =
* Manuális címkegeneráláskor lehet állítani, hogy fizetett e a számla(pro verzió)

= 4.7.2 =
* wc_billingo_plus_gross_unit_price_rounding_precision filterrel beállítható, hogy a bruttó egységárat hogyan kerekítse(alapból forintnál 0)
* Kompatibilitás megjelölése legújabb WP/WC verzióval

= 4.7.1 =
* Számla duplikálás javítás
* Tétel megjegyzés elrejtése opció
* {coupon_description}, {coupon_amount}, {coupon_code} cserekódok használhatók a kedvezményes tétel és leírás mezőkben
* Fizetettnek jelölés automatizálás
* Sztornózás után új számla létrehozás hibajavítása
* Kompatibilitás megjelölése legújabb WP/WC verzióval

= 4.7 =
* Manuális számlakészítéskor a számla megjegyzés mezőben ha + karakterrel kezded, akkor hozzáadja a beállításokban megadott megjegyzés végéhez, nem felülírja
* K.AFA áfakulcs felismerése
* Sztornózáskor opcionálisan meg lehet adni a sztornózás okát, amit a számlán megjegyzésként tüntet fel

= 4.6.11 =
* Kompatibilitás megjelölése legújabb WP/WC verzióval
* wc_billingo_plus_deposit_proform_compat filter: ha nem működik rendesen a számla kiállítás előleg vagy díjbekérő alapján, akkor így manuálisan menni fog(a billoingo rendszerében nem lesz összekötve), számla megjegyzésben feltünteti a hivatkozott dokumentumot
* Beállításokban meg lehet adni egyedi rendelés státusz ID-kat, ha az automatizálás nem működik

= 4.6.10 =
* PHP 8.0+ javítás
* Tétel megjegyzés javítás
* IPN bugfix
* Kompatibilitás megjelölése legújabb WP/WC verzióval

= 4.6.8 =
* Hibajavítás Bookings bővítménynél, ha van külön kedvezmény tétel a számlán

= 4.6.7 =
* Hibajavítás PDF csatolmányokkal kapcsolatban
* Bankszinkron megjelöli fizetettnek a rendelést

= 4.6.6 =
* Díjbekérő / előlegszámla hivatkozások javítása
* Sztornózáskor nem görget az oldal tetejére(js bug javítása)

= 4.6.5 =
* Rendelés táblázatban dupla ár megjelenés javítás
* PHP Warning javítása rendelés oldalon

= 4.6.4 =
* Bookings kompatibilitás javítás
* Kompatibilitás megjelölése legújabb WP/WC verzióval

= 4.6.3 =
* WPML kompatibilitás javítása kategória feltétel esetén, ha nem magyar az alap nyelv
* Translatepress kompatibilitás javítás

= 4.6.2 =
* Adózás típus javítás külföldi vásárló esetén
* WPML kompatibilitás javítása kategória feltétel esetén
* Kompatibilitás megjelölése legújabb WP és WC verziókkal

= 4.6.1 =
* Termék kategória feltétel javítás áfakulcs felülírásnál
* HPOS kompatibilitás javítás
* WPML kompatibilitás javítás

= 4.6 =
* HPOS kompatibilitás
* Számlák / díjbekérők megjelenítése a csomagpontos bővítmény új csomagkövetés oldalán
* Fejlesztőknek: wc_billingo_plus_coupon_invoice_item_details filter a kuponos tételek adatainak módosítására

= 4.5.1 =
* Díjbekérő törlés lehetőség

= 4.5 =
* Haladó beállításoknál pénznem feltétel is válaszható
* Bővítmény telepítése után automatán összepárosítja a WooCommerce fizetési módokat a Billingo fizetési móddal
* Alapértelmezett teljesítési idő beállítható(ma, rendelés ideje, fizetetés ideje)
* Jobb hibaüzenet, ha hiányzik az "API és tömeges számlagenerálás" előfizetés a Billingo fiókodban

= 4.4.5.4 =
* PRO verzió aktiválás/deaktiválás biztonsági javítás

= 4.4.5.3 =
* Product Bundles kompatibilitás javítás

= 4.4.5.2 =
* Nyugtával kapcsolatos hibajavítások

= 4.4.5.1 =
* HuCommerce kompatibilitás javítás(adószám)

= 4.4.5 =
* Fizetve pecsét javítás
* Product Bundles kompatibilitás javítás
* Kompatibilitás megjelölése legújabb WP/WC verzióval

= 4.4.4 =
* Új licensz kulcs kezelés
* Kompatibilitás megjelölése legújabb WP/WC verzióval

= 4.4.3 =
* WooCommerce Bookings kompatibilitás
* Hibajavítás egyedi automatizálással történő piszkozat létrehozáshoz

= 4.4.2 =
* Hibajavítás a haladó beállításoknál lévő bankszámlaszám opcióhoz

= 4.4.1 =
* Hivatalos bővítményből való számlák importálásának javítása

= 4.4 =
* Partnerek csoportosítása: ha ugyanazzal az adattal vásárol valaki, akkor nem hoz létre új partnert a billingo-n
* Nyugta készítés
* Fejlesztőknek: wc_billingo_plus_pdf_downloaded action, ami akkor fut le, ha a PDF fájl le lett töltve
* Fejlesztőknek: wc_billingo_plus_pdf_file_path filter, amivel a PDF letöltési helyét és fájlnevet lehet módosítani
* Kompatibilitás ellenőrzése és megjelölése legújabb WP és WC verziókkal

= 4.3.5 =
* Fizetési határidő javítás egyedi automatizálásnál

= 4.3.4 =
* Beállítások oldal hibajavítás

= 4.3.3 =
* IPN hibajavítás

= 4.3.2 =
* Egyedi automatizálás dátumok(teljesítés, fizetési határidő) javítása

= 4.3.1 =
* Bankszinkron díjbekérővel is működik

= 4.3 =
* WCFM kompatibilitás(több fiók használata esetén kiválasztható feltételnek a vendor)
* Áfakulcs jogcíme beállítható
* Százalékos áfakulcs felülírás hibajavítás

= 4.2.2 =
* Bankszinkron bugfix

= 4.2 =
* Bankszinkron támogatás: ha a Billingo felületén összepárosítás egy banki tranzakcióval egy fizetetlen számlát, akkor a WooCommerce-ben lévő számla is fizetett lesz és rendelés státuszt is tud módosítani automatán(PRO verzió)

= 4.1.2 =
* Termék kategória is kiválasztható feltételnek több fiók használatakor

= 4.1.1 =
* Adószám mentés hibajavítás

= 4.1 =
* Shortcode használható a számla és tétel megjegyzésekben
* Az EU VAT Number Assistant bővítménynél csak akkor írja rá a számlára az adószámot, ha valid volt az adószám
* Hibajavítás egyedi termék ár beállításhoz
* Hibajavítás visszatérítések megjelenítéséhez
* WC 5.5.2 és WP 5.8 kompatibilitás ellenőrzése

= 4.0 =
* Új automatizálás beállítások, amivel a Billingo dokumentumok létrehozását rendelés státuszokhoz, rendelés létrehozásához és sikeres fizetésehez társíthatod. Minden automatizálás mellé beállíthatsz különböző feltételeket(például fizetési mód) és megadhatod a teljesítés idejét, fizetési határidőt és a fizetettnek jelölést.
* Új áfakulcs felülírás beállítások: különböző feltétek szerint felülírhatod a számlán megjelenő tételek áfakulcsát
* Haladó beállítások, ahol a bankszámlaszámot, számlatömböt és nyelvet lehet feltételek szerint felülírni
* Termék beállításokban van lehetőség a számlán megjelenő egyedi árat beállítani, illetve elrejteni a tételt a számláról
* Ha a háttérben kell létrehozni számlákat(pl csoportos műveletek útján), akkor kevésbé terheli a rendszert és gyorsabban működik
* Visszatérítés megjelenítése a számlán
* WP 5.7.2 és WC 5.3 kompatibilitás ellenőrzése / megjelölése

= 3.2.2 =
* NAV-os adószám ellenőrzés javítás
* A számla megjegyzésben feltüntethető a szállítási cím({shipping_address}) és a vásárlói megjegyzés({customer_note}). További placeholder egyszerűen hozzáadható a wc_billingo_plus_get_order_note_placeholders filterrel.
* Adószám mező javítások
* Kompatibilitás megjelölése WooCommerce 5.2-vel és WordPress 5.7-el
* Beállítások link hozzáadása a WooCommerce kezdőoldalhoz, hogy ne kelljen mindig az Integráció menüig elnavigálni

= 3.2.2 =
* Kompatibilitás megjelölése WooCommerce 5.0-val és WordPress 5.6.1-el

= 3.2.1.2 =
* Pro verizó bugfix

= 3.2.1.1 =
* WooCommerce Advanced Quantity kompatibilitás javítása

= 3.2.1 =
* WooCommerce Checkout Manager kompatibilitás javítása
* WooCommerce Advanced Quantity kompatibilitás javítása
* Wordpress 5.6 kompatibilitás
* WooCommerce 4.8 kompatibilitás

= 3.2 =
* Előlegszámla(...vagy előleg számla?) készítés(PRO verzió)
* Egy olyan hiba javítása, ami duplikált számlakészítést okozhatott
* Számla megjegyzésnél feltételnek kiválasztható, hogy EU-n belüli, vagy kívüli-e a vásárló
* WooCommerce Currency Switcher by WooBeWoo kompatibilitás
* Bővítmény méretének csökkentése
* Kompatibilitás megjelölése WooCommerce 4.7.1-el

= 3.1.1 =
* Ingyenes tétel áfakulcs javítása
* PHP hiba javítása hibás számlakészítés üzenetnél
* Adószám mező kompatibilitás a Checkout Manager for WooCommerce bővítménnyel
* Kompatibilitás megjelölése a legújabb WC verzióval(4.7.0)

= 3.1.0.2 =
* Paylike fizetési mód is kiválasztható

= 3.1.0.1 =
* Fordítással kapcsolatos javítások

= 3.1 =
* Csoportos műveletekben van egy új dokumentum készítés opció, amivel egyedi paraméterekkel(fizetési határidő, teljesítés ideje, fiók stb...) lehet csoportosan létrehozni dokumentumokat(PRO verzió)
* Előnézet funkció: számlakészítés előtt meg tudod nézni, hogy hogyan fog kinézni a számla(PRO)
* Termék beállításoknál kikapcsolható az automata számlakészítés: ha a rendelésben van az adott termék, nem készül számla automatán
* Translatepress kompatibilitás: a kiválasztott nyelv alapján készül a számla is
* Ha több fiókot használsz, működni fog a sztornózás manuális számlakészítés után
* Ha van díjbekérő a rendeléshez, az alapján készül a számla(így fizetettnek lesz jelölve a díjbekérő a billingo rendszerében)
* Opciók gombra kattintáskor nem görget fel az oldal tetejére
* Kompatibilitás a WooCommerce EU Vat Number 2.3.21+ verziókkal
* A rendelés előnézetben is látszódnak a számlák
* Fejlesztői módban az előnézeten látszik a Billingo-nak küldött adat is(fejlesztéshez és supporthoz is hasznos)
* Ha egy rendelésnél ki van kapcsolva a számlakészítés, nem látszódik feleslegesen a fizetettnek jelölés gomb
* Adószám ellenőrzés működik a fiókom oldalon is
* Angol a bővítmény nyelve, magyar nyelv mellékelve
* Manuális számlakészítéskor ha a fizetési mód szerint már fizetett a számla, akkor nem látszik a fizetettnek jelölés gomb feleslegesen
* WooCommerce 4.6.1 és WordPress 5.5.3 kompatibilitás megjelölése

= 3.0.1 =
* Devizás számla hibajavítása

= 3.0 =
* A Billingo V3 API-val működik. Fontos, hogy új API kulcsot kell generálnod és néhány más beállítást is át kell nézned. Bővebb infót [itt találsz a módosításokról](https://visztpeter.me/gyik/frissites-a-3-0-as-verziora/).
* Kiválasztható a számlán megjelenő bankszámlaszám(a Billingo felületén lehet megadni több bankszámlaszámot is)

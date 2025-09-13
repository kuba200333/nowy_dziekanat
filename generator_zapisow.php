<?php
// Plik: generator_zapisow.php (Wersja z weryfikacją istniejących zapisów)

// --- KONFIGURACJA ---
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "dziekanat";

// DANE WEJŚCIOWE: Wklej tutaj swoje dane w podanym formacie
$dane_wejsciowe = "
688389915851227164; 55813; Łukasz Niewiński; 31_BO3_Tm_W, S1_I_ang2_B2_C1_gr3, S1_I_L_310, 31_BO3_Tm_L_310, S1_I_31 i 32_W, S1_I_W_31, BO8_Pd_W, S1_I_Ipz1_31_W, BO8_Pd_L_gr2
383683040263536652; 55717; Julia Chmiel; S1_I_L_336, S1_I_33_W, S1_I_ang2_B2_C1_gr3, 33_BO3_Ii_L_336, 33_BO3_Ii_W, BO8_Pd_W, BO8_Pd_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33
498527566618165248; 55583; Alicja Łysko; S1_I_L_334, S1_I_33_W, S1_I_ang2_C1_gr7, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr4
515863221727395871; 55854; Adam Podgórski; S1_I_L_311, 31_BO3_Tm_W, S1_I_ang2_C1_gr8, 31_BO3_Tm_L_311, S1_I_31 i 32_W, S1_I_W_31, BO8_Gk_W, S1_I_Ipz1_31_W, BO8_Gk_L_gr2
339206218692427788; 55796; Antoni Traczyk; S1_I_L_333, 33_BO3_Ai1_L_333, S1_I_33_W, S1_I_ang2_B2 _gr1, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr4
414471236056252417; 55613; Klaudia Krawiec; S1_I_L_333, 33_BO3_Ai1_L_333, S1_I_33_W, S1_I_ang2_C1_gr7, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr6
327164325649317889; 55639; Oliwier Nykiel; 31_BO3_Kb_L_312, S1_I_L_312, S1_I_ang2_B2_C1_gr3, S1_I_31 i 32_W, S1_I_W_31, 31_BO3_Kb_W, BO8_Pd_W, BO8_Pd_L_gr1, S1_I_Ipz1_31_W
554407158821683210; 55756; Katarzyna Pstrokońska; S1_I_L_334, S1_I_33_W, S1_I_ang2_C1_gr8, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr4
399214968832131072; 55828; Michał Tomf; S1_I_L_325, S1_I_ang2_B2 _gr1, 32_BO3_Priw_L_325, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, BO8_Pd_W, BO8_Pd_L_gr3, S1_I_Ipz1_32_W
560845195466506290; 55573; Bartosz Michalski; S1_I_33_W, S1_I_ang2_B2_C1_gr3, S1_I_L_337, 33_BO3_Ii_L_337, 33_BO3_Ii_W, BO8_Gw_W, BO8_Gw_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33
772904304163946519; 55647; Mateusz Parciak; S1_I_33_W, S1_I_ang2_C1_gr8, S1_I_L_331, 33_BO3_Ai1_W, BO8_Gw_W, BO8_Gw_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33, 33_BO3_Ai1_L_331
475706288710615041; 55763; Krzysztof Śledź; S1_I_L_336, S1_I_33_W, S1_I_ang2_B2 _gr1, 33_BO3_Ii_L_336, 33_BO3_Ii_W, BO8_Pd_W, BO8_Pd_L_gr3, S1_I_Ipz1_33_W, S1_I_W_33
403941916540141570; 55726; Adam Ceglarz; S1_I_L_333, 33_BO3_Ai1_L_333, S1_I_33_W, S1_I_ang2_B2_C1_gr4, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr6
487610558837686282; 55673; Bartłomiej Mazur; S1_I_L_323, S1_I_ang2_B2_C1_gr3, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_323, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_32_W
311510300555870210; 55820; Piotr Świstak; S1_I_33_W, S1_I_ang2_B2 _gr1, S1_I_L_331, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33, 33_BO3_Ai1_L_331
386591662585151499; 55617; Dorian Sobierański; S1_I_L_325, S1_I_ang2_C1_gr9, 32_BO3_Priw_L_325, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, BO8_Pd_W, BO8_Pd_L_gr3, S1_I_Ipz1_32_W
764935521867792414; 55840; Szymon Miksza; S1_I_L_333, 33_BO3_Ai1_L_333, S1_I_33_W, S1_I_ang2_B2_C1_gr3, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr4
229219961296519169; 55641; Błażej Skiba; S1_I_L_333, 33_BO3_Ai1_L_333, S1_I_33_W, S1_I_ang2_C1_gr9, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr4
394206446943862804; 55830; Hubert Kobesko; S1_I_L_336, S1_I_33_W, S1_I_ang2_C1_gr6, 33_BO3_Ii_L_336, 33_BO3_Ii_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr7
806104501513486338; 55650; Hulia Oleksii; 33_BO3_Ai1_L_330, S1_I_L_330, S1_I_33_W, S1_I_ang2_C1_gr5, 33_BO3_Ai1_W, BO8_Gw_W, BO8_Gw_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33
603175669781757952; 55737; Ivan Melnykov; S1_I_L_335, S1_I_33_W, S1_I_ang2_B2_C1_gr3, 33_BO3_Ai1_L_335, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr2
1005069354670501948; 53877; Dominik Piwowarski; S1_I_L_320, S1_I_ang2_C1_gr8, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_320, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr8
585406996220477440; 55678; Marcel Korewo; S1_I_L_321, S1_I_ang2_C1_gr7, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_321, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr7
309415582632247298; 55804; Kacper Mazurkiewicz; S1_I_L_336, S1_I_33_W, S1_I_ang2_B2_C1_gr3, 33_BO3_Ii_L_336, 33_BO3_Ii_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr5
422761027055386635; 51642; Mateusz Zwierzchowski; S1_I_L_335, S1_I_33_W, S1_I_ang2_B2 P_gr2, 33_BO3_Ai1_L_335, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33
505477271763681291; 55732; Weronika Kadłubowska; 31_BO3_Tm_W, S1_I_niem2_B1 P_gr1, S1_I_L_310, 31_BO3_Tm_L_310, S1_I_31 i 32_W, S1_I_W_31, BO8_Pd_L_gr1, S1_I_Ipz1_31_W
777241377213251645; 55841; Dmytro Terletskyi; S1_I_L_335, S1_I_33_W, S1_I_ang2_B2 _gr1, 33_BO3_Ai1_L_335, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr2
546688912777805845; 55590; Mikołaj Borowski; S1_I_L_323, S1_I_ang2_B2_C1_gr4, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_323, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_32_W
381813133691584517; 55709; Dominik Gonciarz; S1_I_w-f1_gr8_TF
522733061688000512; 55827; Maciej Milczarek; S1_I_33_W, S1_I_ang2_B2_C1_gr3, S1_I_L_337, 33_BO3_Ii_L_337, 33_BO3_Ii_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr8
460181845510979585; 55638; Oktawiusz Wierzchoń; S1_I_L_323, S1_I_ang2_B2 P_gr2, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_323, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_32_W
426753665643315200; 55604; Bartłomiej Guź; 33_BO3_Ai1_L_330, S1_I_L_330, S1_I_33_W, S1_I_ang2_C1_gr5, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33
719936745877274688; 55742; Zakhar Semiankevich; S1_I_L_313, 31_BO3_Kb_L_313, S1_I_ang2_B2_C1_gr4, S1_I_31 i 32_W, S1_I_W_31, 31_BO3_Kb_W, BO8_Gw_W, BO8_Gw_L_gr3, S1_I_Ipz1_31_W
576767786270064650; 55687; Kamil Kaczorek; S1_I_L_320, S1_I_ang2_C1_gr6, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_320, BO8_Gw_W, BO8_Gw_L_gr3, S1_I_Ipz1_32_W
306843369034743810; 55792; Piotr Kozera; S1_I_L_333, 33_BO3_Ai1_L_333, S1_I_33_W, S1_I_ang2_C1_gr7, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr7
363373970193645569; 55596; Oliwier Szwałek; S1_I_L_334, S1_I_33_W, S1_I_ang2_B2 _gr1, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr4
296620618911186965; 55642; Tymoteusz Sondel; S1_I_L_323, S1_I_ang2_C1_gr9, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_323, BO8_Gw_W, BO8_Gw_L_gr3, S1_I_Ipz1_32_W
419535219889012738; 53660; Jakub Sykuła; S1_I_L_334, S1_I_33_W, S1_I_ang2_C1_gr9, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr4
380750703833907214; 55798; Krzysztof Ciepłucha; 31_BO3_Kb_L_312, S1_I_L_312, S1_I_ang2_B2 P_gr2, S1_I_31 i 32_W, S1_I_W_31, 31_BO3_Kb_W, BO8_Pd_W, BO8_Pd_L_gr1, S1_I_Ipz1_31_W
148160126413438976; 55699; Aleksander Iwan; 31_BO3_Tm_W, S1_I_ang2_C1_gr6, S1_I_L_310, 31_BO3_Tm_L_310, S1_I_31 i 32_W, S1_I_W_31, BO8_Pd_W, BO8_Pd_L_gr1, S1_I_Ipz1_31_W
327873518920269834; 55770; Michał Kapciak; 33_BO3_Ai1_L_330, S1_I_L_330, S1_I_33_W, S1_I_ang2_C1_gr6, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33
277837001682583554; 55571; Piotr Guzowski; S1_I_L_333, 33_BO3_Ai1_L_333, S1_I_33_W, S1_I_ang2_C1_gr5, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr4
425560738787098630; 55588; Joanna Zdebiak; S1_I_33_W, S1_I_ang2_B2 P_gr2, S1_I_L_332, 33_BO3_Ai1_L_332, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr.5, S1_I_Ipz1_33_W, S1_I_W_33
768825904855384085; 55589; Monika Kocisz; S1_I_L_320, S1_I_ang2_C1_gr6, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_320, BO8_Pd_W, BO8_Pd_L_gr1, S1_I_Ipz1_32_W
328654259661045762; 55555; Mateusz Rembas; S1_I_L_321, S1_I_ang2_C1_gr8, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_321, BO8_Pd_W, BO8_Pd_L_gr3, S1_I_Ipz1_32_W
697148657706991616; 55771; Rafał Siewierski; S1_I_L_311, 31_BO3_Tm_W, S1_I_ang2_C1_gr9, 31_BO3_Tm_L_311, S1_I_31 i 32_W, S1_I_W_31, BO8_Gk_W, S1_I_Ipz1_31_W, BO8_Gk_L_gr8
696042591736561816; 55789; Michał Jaz; S1_I_L_322, S1_I_ang2_C1_gr6, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_322, BO8_Gw_W, S1_I_Ipz1_32_W, BO8_Gw_L_gr2
559156781159284738; 55582; Hubert Ślęczka; 31_BO3_Kb_L_312, S1_I_L_312, S1_I_ang2_B2 _gr1, S1_I_31 i 32_W, S1_I_W_31, 31_BO3_Kb_W, BO8_Gk_W, S1_I_Ipz1_31_W, BO8_Gk_L_gr2
319784054734192640; 55719; Jakub Figiel; S1_I_L_320, S1_I_ang2_C1_gr5, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_320, BO8_Pd_W, S1_I_Ipz1_32_W, BO8_Pd_L_gr2
288003569792057344; 55757; Bartosz Poszelężny; S1_I_L_334, S1_I_33_W, S1_I_ang2_C1_gr8, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr4
283204983262937088; 55787; Kacper Sobczak; S1_I_L_324, S1_I_ang2_C1_gr9, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_324, BO8_Gw_W, BO8_Gw_L_gr3, S1_I_Ipz1_32_W
605825233349246980; 55778; Aleksandra Czereszkiewicz; S1_I_L_334, S1_I_33_W, S1_I_ang2_C1_gr5, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr4
587643483544879125; 55563; Daniel Wojtaś; S1_I_33_W, S1_I_ang2_B2 P_gr2, S1_I_L_337, 33_BO3_Ii_L_337, 33_BO3_Ii_W, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_33_W, S1_I_W_33
692296018229985362; 53535; Katarzyna Bronk; S1_I_33_W, S1_I_ang2_B2_C1_gr4, S1_I_L_331, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, 33_BO3_Ai1_L_331, BO8_Gk_L_gr6
1257972234333454408; 55623; Yehor Ryzhov; S1_I_L_311, 31_BO3_Tm_W, S1_I_ang2_C1_gr8, 31_BO3_Tm_L_311, S1_I_31 i 32_W, S1_I_W_31, BO8_Gk_W, S1_I_Ipz1_31_W, BO8_Gk_L_gr8
408685079108386828; 55576; Maciej Tarnowski; 31_BO3_Tm_W, S1_I_ang2_B2 _gr1, S1_I_L_310, 31_BO3_Tm_L_310, S1_I_31 i 32_W, S1_I_W_31, BO8_Pd_W, BO8_Pd_L_gr3, S1_I_Ipz1_31_W
226766690430615552; 55748; Jędrzej Zawalski; S1_I_L_324, S1_I_ang2_B2 P_gr2, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_324, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr6
636212009498705930; 55807; Tomasz Skubisz; S1_I_33_W, S1_I_ang2_C1_gr9, S1_I_L_332, 33_BO3_Ai1_L_332, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr.5, S1_I_Ipz1_33_W, S1_I_W_33
290159083636654080; 55821; Konrad Ołdakowski; S1_I_L_321, S1_I_ang2_B2_C1_gr3, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_321, BO8_Gk_W, BO8_Gk_L_gr.5, S1_I_Ipz1_32_W
611965334655598605; 55621; Jakub Wierciński; S1_I_33_W, S1_I_ang2_B2_C1_gr4, S1_I_L_331, 33_BO3_Ai1_W, BO8_Gw_W, BO8_Gw_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33, 33_BO3_Ai1_L_331
596665293850411018; 55624; Volodymyr Vyshnovetskyi; S1_I_L_313, 31_BO3_Kb_L_313, S1_I_ang2_B2 _gr1, S1_I_31 i 32_W, S1_I_W_31, 31_BO3_Kb_W, BO8_Gw_W, S1_I_Ipz1_31_W, BO8_Gw_L_gr2
307897084433137664; 55670; Mateusz Kusztelski; S1_I_L_325, S1_I_ang2_C1_gr7, 32_BO3_Priw_L_325, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr7
362982152293449728; 55753; Adam Mańczuk; 31_BO3_Tm_W, S1_I_ang2_C1_gr7, S1_I_L_310, 31_BO3_Tm_L_310, S1_I_31 i 32_W, S1_I_W_31, BO8_Pd_W, S1_I_Ipz1_31_W, BO8_Pd_L_gr2
724351740559884380; 55692; Michał Kulczyński; 31_BO3_Kb_L_312, S1_I_L_312, S1_I_ang2_C1_gr7, S1_I_31 i 32_W, S1_I_W_31, 31_BO3_Kb_W, BO8_Gw_W, BO8_Gw_L_gr1, S1_I_Ipz1_31_W
766740713667952680; 55741; Yelyzaveta Poplawska; S1_I_33_W, S1_I_ang2_B2_C1_gr4, S1_I_L_332, 33_BO3_Ai1_L_332, 33_BO3_Ai1_W, BO8_Gw_W, BO8_Gw_L_gr3, S1_I_Ipz1_33_W, S1_I_W_33
522318786133753856; 55689; Olaf Olejniczak; S1_I_L_334, S1_I_33_W, S1_I_ang2_B2_C1_gr3, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr4
454543672143380491; 55558; Rafał Żebryk; S1_I_L_336, S1_I_33_W, S1_I_ang2_B2_C1_gr4, 33_BO3_Ii_L_336, 33_BO3_Ii_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr5
618476226276753448; 55672; Izabela Kupiec; S1_I_33_W, S1_I_ang2_C1_gr7, S1_I_L_331, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, 33_BO3_Ai1_L_331, BO8_Gk_L_gr6
292381572051763200; 53804; Kacper Kaźmierski; S1_I_L_334, S1_I_33_W, S1_I_ang2_C1_gr6, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, S1_I_w-f1_gr8_TF, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr4
430442271289245706; 55579; Aleksander Jaruga; S1_I_L_322, S1_I_ang2_B2_C1_gr4, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_322, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr2
530449764068884480; 55585; Konrad Ojrzanowski; S1_I_33_W, S1_I_ang2_B2_C1_gr3, S1_I_L_332, 33_BO3_Ai1_L_332, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr.5, S1_I_Ipz1_33_W, S1_I_W_33
543716202044850187; 55734; Bogdan Szczerbina; S1_I_L_335, S1_I_33_W, S1_I_ang2_B2_C1_gr4, 33_BO3_Ai1_L_335, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr5
686876708015833099; 55819; Szymon Śniegowski; S1_I_33_W, S1_I_ang2_B2 _gr1, S1_I_L_337, 33_BO3_Ii_L_337, 33_BO3_Ii_W, BO8_Gk_W, BO8_Gk_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33
264402208240566274; 53659; Jakub Adamczyk; S1_I_33_W, S1_I_ang2_B2_C1_gr4, S1_I_L_331, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33, 33_BO3_Ai1_L_331
252156508421357569; 55648; Mateusz Fedorowicz; 31_BO3_Tm_W, S1_I_ang2_C1_gr5, S1_I_L_310, 31_BO3_Tm_L_310, S1_I_31 i 32_W, S1_I_W_31, BO8_Gk_W, S1_I_Ipz1_31_W, BO8_Gk_L_gr2
358506925622558720; 55832; Adam Szałkowski; S1_I_L_334, S1_I_33_W, S1_I_ang2_B2 P_gr2, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr4
248848650451550209; 55784; Kacper Misiak; S1_I_L_334, S1_I_33_W, S1_I_ang2_B2_C1_gr3, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr4
570589282516074506; 55799; Robert Półtorak; S1_I_L_334, S1_I_33_W, S1_I_ang2_B2_C1_gr4, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr4
422082374751223840; 55738; Adrianna Świder; S1_I_33_W, S1_I_ang2_B2 _gr1, S1_I_L_337, 33_BO3_Ii_L_337, 33_BO3_Ii_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr8
343111705791823874; 55805; Patryk Woźnicki; S1_I_L_324, S1_I_ang2_B2 P_gr2, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_324, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr6
185017615570305024; 55842; Mateusz Hypś; 31_BO3_Tm_W, S1_I_ang2_C1_gr6, S1_I_L_310, 31_BO3_Tm_L_310, S1_I_31 i 32_W, S1_I_W_31, BO8_Gk_W, S1_I_Ipz1_31_W, BO8_Gk_L_gr7
309042505729703937; 55843; Miłosz Kustosz; S1_I_L_335, S1_I_33_W, S1_I_ang2_C1_gr7, 33_BO3_Ai1_L_335, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr7
1006146814921154644; 55724; Amelia Kucharska; S1_I_L_311, 31_BO3_Tm_W, S1_I_ang2_C1_gr7, 31_BO3_Tm_L_311, S1_I_31 i 32_W, S1_I_W_31, BO8_Pd_W, S1_I_Ipz1_31_W, BO8_Pd_L_gr2
434760781008207874; 55727; Maciej Kucharski; S1_I_L_333, 33_BO3_Ai1_L_333, S1_I_33_W, S1_I_ang2_C1_gr7, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr7
285462242550349825; 55718; Natan Wojnas; 33_BO3_Ai1_L_330, S1_I_L_330, S1_I_33_W, S1_I_ang2_B2 P_gr2, 33_BO3_Ai1_W, BO8_Gw_W, BO8_Gw_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33
383338470564954114; 55594; Aleksandra Azelska; S1_I_33_W, S1_I_ang2_B2_C1_gr4, S1_I_L_332, 33_BO3_Ai1_L_332, 33_BO3_Ai1_W, BO8_Gw_W, BO8_Gw_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33
411615157371011083; 55707; Mateusz Prządka; S1_I_33_W, S1_I_ang2_C1_gr8, S1_I_L_337, 33_BO3_Ii_L_337, 33_BO3_Ii_W, BO8_Pd_W, BO8_Pd_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33
402468682246127617; 55764; Rafał Grzelak; S1_I_L_323, S1_EK_II_PodsZachnaRynkPracy_A, S1_EK_II_W1, S1_I_ang2_C1_gr5, S1_I_31 i 32_W, S1_EK_II_WF_Men, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_323, S1_EK_II_A1, S1_EK_II_Ergonomia_A1, BO8_Gk_W, BO8_Gk_L_gr.5, S1_I_Ipz1_32_W, S1_EK_II_L1
449226892768182293; 55785; Rafał Soska; 33_BO3_Ai1_L_330, S1_I_L_330, S1_I_33_W, S1_I_ang2_C1_gr9, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr4
249482254173208577; 55696; Damian Sporek; S1_I_33_W, S1_I_ang2_C1_gr9, S1_I_L_332, 33_BO3_Ai1_L_332, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr.5, S1_I_Ipz1_33_W, S1_I_W_33
1222224225322930258; 55762; Weronika Waś; S1_I_L_321, S1_I_ang2_B2 P_gr2, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_321, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr4
305742074509000708; 55759; Jakub Szczudło; S1_I_L_320, S1_I_ang2_B2 _gr1, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_320, BO8_Pd_W, BO8_Pd_L_gr3, S1_I_Ipz1_32_W
236805809655382016; 55837; Jakub Michałowski; S1_I_L_323, S1_I_ang2_B2_C1_gr3, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_323, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_32_W
769132471513645066; 55644; Dmytro Ziailyk; S1_I_w-f1_gr7_JT
761220418534506517; 55681; Paulina Strzyżewska; S1_I_33_W, S1_I_ang2_B2 P_gr2, S1_I_L_331, 33_BO3_Ai1_W, BO8_Gw_W, BO8_Gw_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33, 33_BO3_Ai1_L_331
503559920067674112; 55751; Michał Mak; 33_BO3_Ai1_L_330, S1_I_L_330, S1_I_33_W, S1_I_ang2_B2_C1_gr4, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr4
318402339113271297; 55768; Julian Vrtiška; S1_I_33_W, S1_I_ang2_B2 _gr1, S1_I_L_332, 33_BO3_Ai1_L_332, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr.5, S1_I_Ipz1_33_W, S1_I_W_33
340440377787940865; 55675; Damian Sobczuk; 33_BO3_Ai1_L_330, S1_I_L_330, S1_I_33_W, S1_I_ang2_C1_gr9, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr4
303186361723387904; 55765; Bartłomiej Kacperski; N1_I_5 sem_W, BO2_Sig_W, N1_I_L_330B, N1_I_ang2_gr1
378607329404387334; 55769; Jakub Jagodziński; 33_BO3_Ai1_L_330, S1_I_L_330, S1_I_33_W, S1_I_ang2_C1_gr6, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr4
466593298023448586; 55782; Krzysztof Staszkiewicz; S1_I_L_333, 33_BO3_Ai1_L_333, S1_I_33_W, S1_I_ang2_C1_gr9, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr6
402164872298758146; 55660; Michał Zawiliński; S1_I_L_321, S1_I_ang2_B2 P_gr2, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_321, BO8_Gw_W, S1_I_Ipz1_32_W, BO8_Gw_L_gr5
234040988560130048; 55740; Tadeusz Bindas; S1_I_L_311, 31_BO3_Tm_W, S1_I_ang2_B2_C1_gr3, 31_BO3_Tm_L_311, S1_I_31 i 32_W, S1_I_W_31, BO8_Pd_W, S1_I_Ipz1_31_W, BO8_Pd_L_gr2
304220339917946881; 55669; Stanisław Golański; S1_I_L_320, S1_I_ang2_C1_gr5, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_320, BO8_Gk_W, BO8_Gk_L_gr1, S1_I_Ipz1_32_W
888800097301839872; 55600; Mateusz Pachołek; 33_BO3_Ai1_L_330, S1_I_L_330, S1_I_33_W, S1_I_ang2_B2_C1_gr3, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr8
343155200342622208; 55612; Martyna Jabłońska; S1_I_L_333, 33_BO3_Ai1_L_333, S1_I_33_W, S1_I_ang2_C1_gr6, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr6
352591469883949066; 55575; Andrzej Żwirko; S1_I_L_322, S1_I_ang2_B2 P_gr2, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_322, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr2
462680580845207562; 55700; Mateusz Hunicz; S1_I_L_320, S1_I_ang2_C1_gr5, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_320, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr7
360821725623222275; 55714; Rafał Kołodziejczyk; S1_I_L_313, 31_BO3_Kb_L_313, S1_I_ang2_C1_gr6, S1_I_31 i 32_W, S1_I_W_31, 31_BO3_Kb_W, BO8_Pd_W, S1_I_Ipz1_31_W, BO8_Pd_L_gr2
475710214985875456; 55599; Marceli Padjasek; S1_I_L_336, S1_I_33_W, S1_I_ang2_C1_gr8, 33_BO3_Ii_L_336, 33_BO3_Ii_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr5
411640898451931159; 55776; Maksymilian Orbach; S1_I_33_W, S1_I_ang2_B2_C1_gr3, S1_I_L_337, 33_BO3_Ii_L_337, 33_BO3_Ii_W, BO8_Gk_W, BO8_Gk_L_gr.5, S1_I_Ipz1_33_W, S1_I_W_33
344805081071484929; 55615; Mikołaj Wróbel; S1_I_L_322, S1_I_ang2_B2 P_gr2, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_322, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr2
332108402396364801; 55809; Szymon Wachnik; S1_I_L_311, 31_BO3_Tm_W, S1_I_ang2_B2 P_gr2, 31_BO3_Tm_L_311, S1_I_31 i 32_W, S1_I_W_31, BO8_Pd_W, BO8_Pd_L_gr3, S1_I_Ipz1_31_W
544615217007689738; 55602; Bartłomiej Tracz; S1_I_33_W, S1_I_ang2_B2 _gr1, S1_I_L_332, 33_BO3_Ai1_L_332, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr.5, S1_I_Ipz1_33_W, S1_I_W_33
279374201755009025; 56177; Mieszko Kin; S1_I_L_320, S1_I_ang2_C1_gr6, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_320, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr8
307974288521297922; 55592; Paweł Ewald; S1_I_L_322, S1_I_ang2_C1_gr5, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_322, BO8_Gw_W, S1_I_Ipz1_32_W, BO8_Gw_L_gr2
1161213507468001344; 56419; Andrii Tynskyi; S1_I_L_334, S1_I_33_W, S1_I_ang2_B2 _gr1, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr.5, S1_I_Ipz1_33_W, S1_I_W_33
628298361715294223; 46641; Filip Sobczyński; S1_I_L_334, S1_I_33_W, S1_I_ang2_B2 _gr1, 33_BO3_Ai1_L_334, 33_BO3_Ai1_W, BO8_Gw_W, BO8_Gw_L_gr3, S1_I_Ipz1_33_W, S1_I_W_33
634824053143896100; 55656; Gracjan Grzeszczak; S1_I_L_325, S1_I_ang2_B2_C1_gr4, 32_BO3_Priw_L_325, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, S1_I_Ipz1_32_W
647488559145549844; 55552; Kacper Małecki; S1_I_L_324, S1_I_ang2_C1_gr7, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_324, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr7
413769921072201729; 55666; Błażej Nojszewski; S1_I_L_320, S1_I_ang2_B2_C1_gr4, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_320, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr8
535879570935513088; 55556; Bartosz Wieczorek; S1_I_33_W, S1_I_ang2_B2 P_gr2, S1_I_L_331, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33, 33_BO3_Ai1_L_331
688050722151071753; 55567; Jakub Uziębło; S1_I_L_333, 33_BO3_Ai1_L_333, S1_I_33_W, S1_I_ang2_B2 _gr1, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr4
287198525010477057; 55603; Benedykt Almakiewicz; S1_I_L_323, S1_I_ang2_B2 P_gr2, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_323, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_32_W
409442899353272341; 55857; Igor Borzyszkowski; S1_I_33_W, S1_I_ang2_B2_C1_gr4, S1_I_L_331, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33, 33_BO3_Ai1_L_331
479019038505435147; 55760; Norbert Świstak; S1_I_L_325, S1_I_ang2_B2 _gr1, 32_BO3_Priw_L_325, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_32_W
1018807168700518411; 55722; Dmytro Mahaliuk; S1_I_L_335, S1_I_33_W, S1_I_ang2_B2_C1_gr4, 33_BO3_Ai1_L_335, 33_BO3_Ai1_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr2
320228594116853782; 55580; Kamil Boruciński; S1_I_L_325, S1_I_ang2_C1_gr7, 32_BO3_Priw_L_325, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr2
482924218158284811; 55794; Wiktor Halak; 31_BO3_Kb_L_312, S1_I_L_312, S1_I_ang2_C1_gr5, S1_I_31 i 32_W, S1_I_W_31, 31_BO3_Kb_W, BO8_Pd_W, BO8_Pd_L_gr1, S1_I_Ipz1_31_W
398569145370673152; 55848; Eryk Pacyna; S1_I_33_W, S1_I_ang2_C1_gr8, S1_I_L_331, 33_BO3_Ai1_W, BO8_Gw_W, BO8_Gw_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33, 33_BO3_Ai1_L_331
249645174345564171; 55659; Bartosz Piróg; S1_I_L_322, S1_I_ang2_C1_gr8, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_322, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr2
457246539253940224; 55816; Kacper Marciszewski; S1_I_L_324, S1_I_niem2_B1 P_gr1, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_324, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr8
358301093924438026; 54439; Adam Wojturski; S1_I_L_320, S1_I_ang2_B2 P_gr2, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_320, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_32_W
1072290896692904069; 56605; Michał Chrzanowski; 31_BO3_Kb_L_312, S1_I_L_312, S1_I_ang2_B2_C1_gr4, S1_I_31 i 32_W, S1_I_W_31, 31_BO3_Kb_W, BO8_Gw_W, BO8_Gw_L_gr1, S1_I_Ipz1_31_W
296320665257115649; 55695; Oleksandr Frankowski; S1_I_L_321, S1_I_ang2_C1_gr5, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_321, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr6
344951821611302923; 55733; Adrian Zbiciak; 31_BO3_Kb_L_312, S1_I_L_312, S1_I_ang2_B2 P_gr2, S1_I_31 i 32_W, S1_I_W_31, 31_BO3_Kb_W, BO8_Gk_W, S1_I_Ipz1_31_W, BO8_Gk_L_gr2
327899583038357506; 55825; Jakub Bartoszewicz; S1_I_33_W, S1_I_ang2_C1_gr7, S1_I_L_332, 33_BO3_Ai1_L_332, 33_BO3_Ai1_W, BO8_Gw_W, BO8_Gw_L_gr3, S1_I_Ipz1_33_W, S1_I_W_33
415913246558650370; 55783; Nikita Tolstoi; S1_I_L_311, 31_BO3_Tm_W, S1_I_ang2_B2 _gr1, 31_BO3_Tm_L_311, S1_I_31 i 32_W, S1_I_W_31, BO8_Pd_W, BO8_Pd_L_gr3, S1_I_Ipz1_31_W
285829157169135616; 55667; Patryk Bąk; 33_BO3_Ai1_L_330, S1_I_L_330, S1_I_33_W, S1_I_ang2_C1_gr7, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr4
473213917800300557; 55611; Filip Chwaleba; 33_BO3_Ai1_L_330, S1_I_L_330, S1_I_33_W, S1_I_ang2_B2_C1_gr4, 33_BO3_Ai1_W, BO8_Gk_W, BO8_Gk_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33
335486855422279682; 55661; Michał Trzaska; S1_I_L_324, S1_I_ang2_B2 _gr1, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_324, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr7
564491225894486016; 55560; Wiktor Kokotowski; 33_BO3_Ai1_L_330, S1_I_L_330, S1_I_33_W, S1_I_ang2_C1_gr6, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr4
1060116402427658311; 55750; Daniel Jeleń; S1_I_33_W, S1_I_ang2_C1_gr6, S1_I_L_332, 33_BO3_Ai1_L_332, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr7
892455637160722532; 55715; Maciej Kalemba; S1_I_L_322, S1_I_ang2_C1_gr6, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_322, BO8_Gw_W, S1_I_Ipz1_32_W, BO8_Gw_L_gr2
395976569417367552; 55743; Mikołaj Odzeniak; S1_I_L_322, S1_I_ang2_B2_C1_gr3, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_322, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr2
376781097998221324; 55806; Adrian Romański; S1_I_L_323, S1_I_ang2_C1_gr8, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_323, BO8_Gk_W, BO8_Gk_L_gr.5, S1_I_Ipz1_32_W
361544603892973568; 55702; Olaf Stokłos; S1_I_33_W, S1_I_ang2_C1_gr9, S1_I_L_337, 33_BO3_Ii_L_337, 33_BO3_Ii_W, BO8_Pd_W, BO8_Pd_L_gr3, S1_I_Ipz1_33_W, S1_I_W_33
407411393910931458; 55725; Jakub Błaszczyk; 31_BO3_Kb_L_312, S1_I_L_312, S1_I_ang2_C1_gr7, S1_I_31 i 32_W, S1_I_W_31, 31_BO3_Kb_W, BO8_Gw_W, BO8_Gw_L_gr1, S1_I_Ipz1_31_W
333194896657874946; 55761; Ewelina Pruszyńska; 31_BO3_Tm_W, S1_I_ang2_C1_gr8, S1_I_L_310, 31_BO3_Tm_L_310, S1_I_31 i 32_W, S1_I_W_31, BO8_Gk_W, S1_I_Ipz1_31_W, BO8_Gk_L_gr2
689162489405112345; 55635; Wojciech Podlipniak; S1_I_L_324, S1_I_ang2_C1_gr8, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_324, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr6
523236449466712065; 55779; Mikołaj Duda; 31_BO3_Tm_W, S1_I_ang2_C1_gr5, S1_I_L_310, 31_BO3_Tm_L_310, S1_I_31 i 32_W, S1_I_W_31, BO8_Gk_W, BO8_Gk_L_gr.5, S1_I_Ipz1_31_W
530833824461094932; 55710; Wojciech Grzymała; S1_I_L_336, S1_I_33_W, S1_I_ang2_B2 _gr1, 33_BO3_Ii_L_336, 33_BO3_Ii_W, BO8_Pd_W, BO8_Pd_L_gr1, S1_I_Ipz1_33_W, S1_I_W_33
547079295856541706; 55634; Michał Wilk; 31_BO3_Tm_W, S1_I_ang2_B2 P_gr2, S1_I_L_310, 31_BO3_Tm_L_310, S1_I_31 i 32_W, S1_I_W_31, BO8_Pd_W, BO8_Pd_L_gr1, S1_I_Ipz1_31_W
1173002777522343968; 55628; Aleksandra Paszkowska; S1_I_L_122B, S1_I_12_W
373546641841586178; 55655; Łukasz Brylewski; 31_BO3_Tm_W, S1_I_ang2_B2_C1_gr4, S1_I_L_310, 31_BO3_Tm_L_310, S1_I_31 i 32_W, S1_I_W_31, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_31_W
1063094683871481886; 55716; Natalia Oleksy; S1_I_33_W, S1_I_ang2_B2_C1_gr3, S1_I_L_331, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, 33_BO3_Ai1_L_331, BO8_Gk_L_gr6
705883294100750346; 55616; Andrii Zhupanov; S1_I_L_322, S1_I_ang2_B2 P_gr2, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_322, BO8_Gk_W, S1_I_Ipz1_32_W, BO8_Gk_L_gr2
864089992162836500; 55625; Mykhailo Martynenko; S1_I_L_336, S1_I_33_W, S1_I_ang2_B2_C1_gr4, 33_BO3_Ii_L_336, 33_BO3_Ii_W, BO8_Gw_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gw_L_gr5
304356092253831209; 55817; Gracjan Drogoś; S1_I_L_311, 31_BO3_Tm_W, S1_I_ang2_C1_gr5, 31_BO3_Tm_L_311, S1_I_31 i 32_W, S1_I_W_31, BO8_Pd_W, S1_I_Ipz1_31_W, BO8_Pd_L_gr2
1074689253579178014; 55834; Anna Kapica; S1_I_L_321, S1_I_ang2_C1_gr6, S1_I_31 i 32_W, S1_I_W_32, 32_BO3_Kc-m_W, 32_BO3_Kc-m_L_321, BO8_Pd_W, BO8_Pd_L_gr1, S1_I_Ipz1_32_W
642481582753447987; 55664; Szymon Szadkowski; S1_I_L_333, 33_BO3_Ai1_L_333, S1_I_33_W, S1_I_ang2_C1_gr9, 33_BO3_Ai1_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr2
394914018743025675; 55745; Paweł Usnarski; S1_I_L_324, S1_I_ang2_B2 _gr1, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_324, BO8_Pd_W, BO8_Pd_L_gr3, S1_I_Ipz1_32_W
1339598545505751153; 55801; Jakub Szpakowski; S1_I_L_322, S1_I_ang2_B2 _gr1, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_322, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_32_W
691731751290142760; 55729; Jan Paszkiel; S1_I_L_324, S1_I_ang2_C1_gr8, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_324, BO8_Pd_W, S1_I_Ipz1_32_W, BO8_Pd_L_gr2
398572787595345930; 55831; Artur Mizuła; S1_I_L_322, S1_I_ang2_B2_C1_gr3, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_322, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_32_W
334824686556807178; 55849; Dorian Strzelecki; S1_I_33_W, S1_I_ang2_C1_gr9, S1_I_L_337, 33_BO3_Ii_L_337, 33_BO3_Ii_W, BO8_Gw_W, BO8_Gw_L_gr3, S1_I_Ipz1_33_W, S1_I_W_33
364431191949967362; 55697; Ignacy Oziero; S1_I_L_323, S1_I_ang2_B2_C1_gr3, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_323, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_32_W
1029697654357237890; 55684; Michał Gudowicz; S1_I_33_W, S1_I_ang2_C1_gr5, S1_I_L_337, 33_BO3_Ii_L_337, 33_BO3_Ii_W, BO8_Gk_W, S1_I_Ipz1_33_W, S1_I_W_33, BO8_Gk_L_gr6
303933525923725313; 53978; Michał Świeczak; S1_I_Ipz1_gr3
214506852384374784; 55630; Damian Tomaszewski; S1_I_L_323, S1_I_ang2_B2_C1_gr3, S1_I_31 i 32_W, 32_BO3_Priw_W, S1_I_W_32, 32_BO3_Priw_L_323, BO8_Gk_W, BO8_Gk_L_gr3, S1_I_Ipz1_32_W
766590333408509962; 55686; Anhelina Vilchynska; S1_I_33_W, S1_I_ang2_B2_C1_gr4, S1_I_L_332, 33_BO3_Ai1_L_332, 33_BO3_Ai1_W, BO8_Gw_W, BO8_Gw_L_gr3, S1_I_Ipz1_33_W, S1_I_W_33
";
// --------------------

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

echo "<h1>Generator Skryptu SQL do Zapisów na Zajęcia</h1>";

$sql_output = "-- Wygenerowano " . date('Y-m-d H:i:s') . "\n";
$sql_output .= "-- Skrypt zapisuje studentów na zajęcia, pomijając istniejące już zapisy.\n\n";
$log_bledow = [];
$licznik_nowych_zapisow = 0;
$licznik_pominietych = 0;

// Przygotowujemy zapytanie do sprawdzania, czy zapis już istnieje
$stmt_check = $conn->prepare("SELECT zapis_id FROM ZapisyStudentow WHERE numer_albumu = ? AND zajecia_id = ?");

// Przetwarzanie danych wejściowych
$linie = explode("\n", trim($dane_wejsciowe));

foreach ($linie as $linia) {
    $czesci = explode(';', $linia);
    if (count($czesci) < 4) continue;

    $numer_albumu = trim($czesci[1]);
    $grupy_str = trim($czesci[3]);
    
    $grupy = array_unique(array_map('trim', explode(',', $grupy_str)));

    if (empty($grupy) || empty($numer_albumu)) continue;



    $grupy_in_sql = "'" . implode("','", array_map([$conn, 'real_escape_string'], $grupy)) . "'";

    $zajecia_sql = "
        SELECT z.zajecia_id
        FROM Zajecia z
        JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
        WHERE g.nazwa_grupy IN ($grupy_in_sql)
    ";
    
    $result = $conn->query($zajecia_sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $zajecia_id = $row['zajecia_id'];

            // SPRAWDZANIE, CZY ZAPIS JUŻ ISTNIEJE
            $stmt_check->bind_param("ii", $numer_albumu, $zajecia_id);
            $stmt_check->execute();
            $check_result = $stmt_check->get_result();

            if ($check_result->num_rows === 0) {
                // Jeśli nie istnieje, generujemy zapytanie INSERT
                $sql_output .= "INSERT INTO ZapisyStudentow (numer_albumu, zajecia_id) VALUES ('$numer_albumu', '$zajecia_id');\n";
                $licznik_nowych_zapisow++;
            } else {
                // Jeśli istnieje, pomijamy i zliczamy
                $licznik_pominietych++;
            }
        }
    } else {
        $log_bledow[] = "Nie znaleziono żadnych zajęć dla grup studenta $numer_albumu: " . implode(', ', $grupy);
    }
}

echo "<h2>Zakończono generowanie.</h2>";
echo "<p style='color:green;'>Liczba nowych zapisów do dodania: <strong>$licznik_nowych_zapisow</strong></p>";
echo "<p style='color:orange;'>Liczba istniejących zapisów, które pominięto: <strong>$licznik_pominietych</strong></p>";

if (!empty($log_bledow)) {
    echo "<h3>Szczegółowy log błędów/ostrzeżeń:</h3>";
    echo "<textarea rows='5' style='width:100%; font-family: monospace;'>" . implode("\n", $log_bledow) . "</textarea>";
}

echo "<h2>Gotowy Skrypt SQL</h2>";
echo "<p>Skopiuj całą poniższą zawartość i wklej do zakładki SQL w phpMyAdmin, aby dodać brakujące zapisy.</p>";
echo "<textarea rows='20' style='width:100%; font-family: monospace;'>" . htmlspecialchars($sql_output) . "</textarea>";

$conn->close();
?>
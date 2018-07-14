<?php
//Largeur, hauteur, marge, hauteur cartouche
$formats =[
           'A4L' => ['largeur_page'=>29.7, 'hauteur_page'=>21.0, 'marge'=>1, 'hauteur_cartouche'=>1.5, 'orientation'=>'L'],
           'A3P' => ['largeur_page'=>29.7, 'hauteur_page'=>42.0, 'marge'=>1, 'hauteur_cartouche'=>1.5, 'orientation'=>'P'],
           'A3L' => ['largeur_page'=>42.0, 'hauteur_page'=>29.7, 'marge'=>1, 'hauteur_cartouche'=>1.5, 'orientation'=>'L'],
           'A2P' => ['largeur_page'=>42.0, 'hauteur_page'=>59.4, 'marge'=>1, 'hauteur_cartouche'=>1.5, 'orientation'=>'P'],
           'A2L' => ['largeur_page'=>59.4, 'hauteur_page'=>42.0, 'marge'=>1, 'hauteur_cartouche'=>1.5, 'orientation'=>'L'],
           'A1P' => ['largeur_page'=>59.4, 'hauteur_page'=>84.1, 'marge'=>1, 'hauteur_cartouche'=>1.5, 'orientation'=>'P'],
           'A1L' => ['largeur_page'=>84.1, 'hauteur_page'=>59.4, 'marge'=>1, 'hauteur_cartouche'=>1.5, 'orientation'=>'L'],
           'A0P' => ['largeur_page'=>84.1, 'hauteur_page'=>118.9,'marge'=>1, 'hauteur_cartouche'=>1.5, 'orientation'=>'P'],
           'A0L' => ['largeur_page'=>118.9,'hauteur_page'=>4.1,  'marge'=>1, 'hauteur_cartouche'=>1.5, 'orientation'=>'L']
           ];

           
foreach ($formats AS $key=>$format)
{
    $formats[$key]['largeur_dessin'] = $format['largeur_page']-($format['marge']*2);
    $formats[$key]['hauteur_dessin'] = $format['hauteur_page']-$format['hauteur_cartouche']-($format['marge']*2);
}
?>
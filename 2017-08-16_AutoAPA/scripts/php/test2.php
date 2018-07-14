<?php
require('../Library/fpdf181/fpdf.php');
require('../Library/fpdf_draw/draw.php');
require('../Library/rpdf/rpdf.php');
require('SU_Draw.php');
require('formats.php');


//Fonction de trie des layers
function SortLayers($layer_1, $layer_2)
{
    global $styles;
    if ($styles[$layer_1['id_style']] == $styles[$layer_2['id_style']]) {return 0;}
    return ($styles[$layer_1['id_style']]< $styles[$layer_2['id_style']]) ? -1 : 1;
    
}

function GetSketchPosition($xy_str)
{
    $xy = str_replace(['~', '(', ')', 'cm'], '', $xy_str); //netoyage
    $xy_exp = explode(', ', $xy, 2);
    return ['y'=>$xy_exp[0], 'x'=>$xy_exp[1]];
}

function ToScale($xy, $meta_page)
{
    global $formats;   

    $x_sketch = $xy['x']/$meta_page['scale']+$formats[$meta_page['key_format']]['marge'];   
    //$y_sketch = $xy_exp[1]/$meta_page['scale']+$formats[$key_format]['marge'];   
    
    $y_sketch = $formats[$meta_page['key_format']]['hauteur_dessin']
                - ($xy['y']/$meta_page['scale'])+$formats[$meta_page['key_format']]['marge'];
    
    return ['x'=>$x_sketch, 'y'=>$y_sketch];
}

function getMetaPage($page_name, $frame_name)
{
    global $formats;
    $meta_page = ['key_format'=>'A4L', 'scale'=>100, 'no'=>1, 'name'=>'Sans titre'];
    $frame_name_exp = explode('*', $page_name);
    $frame_name_exp = explode('*', $frame_name);
    //Format
    if(array_key_exists($frame_name_exp[0], $formats)) {$meta_page['key_format'] = $frame_name_exp[0];}    
    if(isset($frame_name_exp[1])) {$frame_name_exp['scale'] = (int) $frame_name_exp[1];}//Echelle
    if(isset($frame_name_exp[0])) {$meta_page['no'] = (int) $frame_name_exp[0];}//numero
    if(isset($frame_name_exp[1])) {$meta_page['name'] = $frame_name_exp[1];} //nom
    
    return $meta_page;
}

function findStyle(&$layers)
{
    global $styles;
    //Suppresion de tout les layers qui n'ont pas de style et ajout des informations de style dans l'arrays des layers
    foreach($layers as $id_layer => $layer)
    {
          $layer_found =0;
          foreach($styles as $id_style => $style)
          {
            if($layer['layer_name'] == $style['layer_name'])
            {
                $layer_found=1;
                $layers[$id_layer]['id_style'] = $id_style; //enregistrement des informations de syle du layer
                //echo $id_style,' ';
            }
          }
          if($layer_found ==0){unset($layers[$id_layer]);}
    }
}

function drawEdge($entity, $meta_page)
{
    global $pdf;
    
    $xy_start_sketch = ToScale(GetSketchPosition ($entity['start']), $meta_page);
    $xy_end_sketch = ToScale(GetSketchPosition($entity['end']), $meta_page);
    
    $pdf->Line($xy_start_sketch['x'],$xy_start_sketch['y'], $xy_end_sketch['x'], $xy_end_sketch['y']);
}

function drawCurve($entity, $meta_page)
{
    global $pdf;
    
    $curve = $entity["content"];
    
    $isLooped = false;
    if($curve[0]["point"] == $curve[count($curve) - 1]["point"])
    {
        $isLooped = true;
    }
    
    $pt_array = array_map(function ($segment) use ($meta_page) {return ToScale(GetSketchPosition($segment["point"]), $meta_page);}, $curve);
    $pdf->DrawCurve($pt_array, $isLooped);
}

function styleLine($style)
{
    global $pdf;
    
    $edge_color_exp  = explode(',', $style['edge_color'], 3);
    if (count($edge_color_exp)==3){$edge_color=[(int)$edge_color_exp[0], (int)$edge_color_exp[1], (int)$edge_color_exp[2]];} else {$edge_color = [0, 0, 0];}
    $pdf->SetLineStyle(['width' =>$style['edge_thickness']/10, 'cap' => 'round', 'join' => 'round', 'dash' => $style['edge_style'], 'color' =>$edge_color]);
    
    return ($style['edge_thickness'] != 0);
}


function drawEntities($layers, $meta_page)
{
    global $styles, $draw_types;
    
    foreach($draw_types AS $draw_type)
    {
        foreach ($layers AS $layer)
        {
            //echo "yo";
            $style =  $styles[$layer['id_style']];
            if($draw_type["prepStyle"]($style))
            { 
                foreach($layer['content'] AS $entity)
                {
                    if(isset($draw_type[$entity['type']]))
                    {
                        $draw_type[$entity['type']]["drawMethod"]($entity, $meta_page);
                    }                
                }
            }
        }
    }
}

function draw()
{
    global $pdf, $geometry, $formats;
    foreach($geometry AS $page)
    {
        //Mise en array des meta, soit le format papier, l'echelle, numero de page et nom comtenu deans la chaine "name"
        $meta_page = getMetaPage($page['name'], $page['frame_name']);
      
        //Preparariton des Layers
        findStyle($page['content']);
            
        //Trie des layers selon odre de tracé
        usort($page['content'], "SortLayers");
        
        //creation du pdf
        $pdf->AddPage($formats[$meta_page['key_format']]['orientation'], [$formats[$meta_page['key_format']]['largeur_page'],
                            $formats[$meta_page['key_format']]['hauteur_page']]);
    
        //Dessin de tout les traits
        drawEntities($page['content'], $meta_page);
        
        AddFrame($meta_page);
    }
}

function AddFrame($meta_page)
{
    global $formats, $pdf;
    //Ajout des cache sur les marges // cache => x,y,w,h
    $caches = [
               //Haut
               [ 'x'=>0,'y'=>0,'w'=>$formats[$meta_page['key_format']]['largeur_page'],'h'=>$formats[$meta_page['key_format']]['marge']], 
               //Bas
               [ 'x'=>0, 'y'=>$formats[$meta_page['key_format']]['hauteur_page']-$formats[$meta_page['key_format']]['marge']-$formats[$meta_page['key_format']]['hauteur_cartouche'],
                'w'=>$formats[$meta_page['key_format']]['largeur_page'],'h'=>$formats[$meta_page['key_format']]['hauteur_cartouche']+$formats[$meta_page['key_format']]['marge']],
                //gauche
                ['x'=>0, 'y'=>$formats[$meta_page['key_format']]['marge'],'w'=>$formats[$meta_page['key_format']]['marge'],
                'h'=>$formats[$meta_page['key_format']]['hauteur_page']- $formats[$meta_page['key_format']]['marge']- $formats[$meta_page['key_format']]['hauteur_cartouche']],
                 //droite
                ['x'=>$formats[$meta_page['key_format']]['largeur_page']-$formats[$meta_page['key_format']]['marge'], 'y'=>$formats[$meta_page['key_format']]['marge'],
                'w'=>$formats[$meta_page['key_format']]['marge'], 'h'=>$formats[$meta_page['key_format']]['hauteur_page']- $formats[$meta_page['key_format']]['marge']- $formats[$meta_page['key_format']]['hauteur_cartouche']]                 
               ];

    
    foreach($caches AS $cache)
    {
        $pdf->SetFillColor(255,255, 0);
        $pdf->Rect($cache['x'], $cache['y'], $cache['w'], $cache['h'], 'F');
    }
    //Titre gauche
    $titre_1 = utf8_decode('P'.str_pad($meta_page['no'], 2, 0, STR_PAD_LEFT). ' '.$meta_page['name'].' - Echelle 1/'.$meta_page['scale']);
    $pdf->SetFont('Helvetica','',10);
    $pdf->Text(
                   $formats[$meta_page['key_format']]['marge'],
                   $formats[$meta_page['key_format']]['hauteur_page'] - $formats[$meta_page['key_format']]['marge'] -0.42,
                   $titre_1);
    
    $footer = utf8_decode('Parcelle n° 277, commune de Chêne-Bougeries');

    $pdf->Text(
                   $formats[$meta_page['key_format']]['marge'],
                   $formats[$meta_page['key_format']]['hauteur_page'] - $formats[$meta_page['key_format']]['marge'],
                   $footer);    
    
    $titre_2 ='LALA';
    $pdf->SetXY(0, $formats[$meta_page['key_format']]['hauteur_page'] - $formats[$meta_page['key_format']]['marge']- $formats[$meta_page['key_format']]['hauteur_cartouche']);
    
    $pdf->SetFillColor(255,155, 255);
    //$pdf->Cell(29, 0.35, $titre_2, 1, 'LTRB', 0, 'C');
    $pdf->Cell(0,.35,'Titre',0,0,'C');
}




function drawText($entity)
{
    global $pdf;
    $text_angle =0;
    $text_content= $entity['text_content'];
    $text_content_exp = explode('*', $entity['text_content']);
    if(count ($text_content_exp)==2){$angle= (int)$text_content_exp[0]; $text_content=$text_content_exp[1];}
    $pdf->TextWithRotation($xy['x'], $xy['x'], $text_content , $text_angle, 0);
    
}
//Creation du pdf
$pdf = new SU_Draw('L','cm');
$pdf->SetMargins(0,0);
//Recuperation geometry
$geometry_file = '../../data/geometry.json';
$geometry = json_decode(file_get_contents($geometry_file), TRUE);
//Recuperation style
$styles_file = '../../data/styles.json';
$styles = json_decode(file_get_contents($styles_file), TRUE);

$draw_types = [["edge" => ["drawMethod" => "drawEdge"], "curve" => ["drawMethod" => "drawCurve"], "prepStyle" => "styleLine"]];

draw();

$pdf->Output('test.pdf', 'I');









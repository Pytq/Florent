<?php
require('../Library/fpdf181/fpdf.php');
require('../Library/fpdf_draw/draw.php');
require('formats.php');

//Fonction de trie des layers
function SortLayers($layer_1, $layer_2)
{
    global $styles;
    if ($styles[$layer_1['id_style']] == $styles[$layer_2['id_style']]) {return 0;}
    return ($styles[$layer_1['id_style']]< $styles[$layer_2['id_style']]) ? -1 : 1;
    
}
function GetSketchPosition ($xy)
{
    global $meta_page, $formats, $key_format;
    
    $xy = str_replace(['~', '(', ')', 'cm'], '', $xy); //netoyage
    $xy_exp = explode(', ', $xy, 2);
    $x_sketch = $xy_exp[0]/$meta_page['scale']+$formats[$key_format]['marge'];   
    //$y_sketch = $xy_exp[1]/$meta_page['scale']+$formats[$key_format]['marge'];   
    
    $y_sketch =  $formats[$key_format]['hauteur_dessin'] - ($xy_exp[1]/$meta_page['scale'])+$formats[$key_format]['marge'];
    
    return ['x'=>$x_sketch, 'y'=>$y_sketch];
}

//Creation du pdf
$pdf=new PDF_Draw('L','cm');

//Recuperation geometry
$file1 ='../../data/geometry.json';
$geometry = json_decode(file_get_contents($file1), TRUE);
//Recuperation style
$file2='../../data/styles.json';
$styles = json_decode(file_get_contents($file2), TRUE);

// Boucle des pages
foreach($geometry AS $page)
{
    //Mise en array des meta, soit le format papier, l'echelle, numero de page et nom comtenu deans la chaine "name"
    $meta_page = ['key_format'=>'A4L', 'scale'=>100, 'no'=>1, 'name'=>'Sans titre'];
    $meta_page_exp = explode('*', $page['name']);
    
    //Format
    if(array_key_exists($meta_page['key_format'], $formats)) {$meta_page['key_format'] = $meta_page_exp[0];}    
    if(isset($meta_page_exp[1])){$meta_page['scale']=(int) $meta_page_exp[1];}//Echelle
    if(isset($meta_page_exp[2])){$meta_page['no']=(int) $meta_page_exp[2];}//numero
    if(isset($meta_page_exp[3])){$meta_page['name']=$meta_page_exp[3];} //nom
     
    //Preparariton des Layers    
    $layers = $page['content'];
    
        
    //Suppresion de tout les layers qui n'ont pas de style et ajout des informations de style dans l'arrays des layers
    foreach($layers as $id_layer => $layer)
    {
          $layer_found =0;
          //echo '<br>',$layer['layer_name'],'<br>'; 
          foreach($styles as $id_style => $style)
          {
            //echo '==>',$style['layer_name'],'<br>';
            if($layer['layer_name'] == $style['layer_name'])
            {
                $layer_found=1;
                $layers[$id_layer]['id_style'] = $id_style; //enregistrement des informations de syle du layer
            }
          }
          if($layer_found ==0){unset($layers[$id_layer]);}
    }
    //print_r($layers);
    
    //Trie des layers selon odre de tracé
    usort($layers, "SortLayers");
    
    //creation du pdf
    $pdf->AddPage('L', [$formats[$key_format]['largeur_page'],  $formats[$key_format]['hauteur_page']]);


    //Dessin de tout les traits
    foreach ($layers AS $layer)
    {
        //Gestion du style
        $style =  $styles[$layer['id_style']];
        
        //Dessin des traits
        if($style['edge_thickness']>0)
        {
            //Gestion_couleur
            
            $edge_color_exp  = explode(',', $style['edge_color'], 3);
            if (count($edge_color_exp)==3){$edge_color=[(int)$edge_color_exp[0], (int)$edge_color_exp[1], (int)$edge_color_exp[2]];} else {$edge_color = [0, 0, 0];}
            $pdf->SetLineStyle(['width' =>$style['edge_thickness']/10, 'cap' => 'round', 'join' => 'round', 'dash' => $style['edge_style'], 'color' =>$edge_color]);
            
            $edge_style_param= array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => '10,20,5,10', 'phase' => 10, 'color' => array(255, 0, 0));
            //boucles sur les entities
            foreach($layer['content'] AS $entity)
            {
              
                //Ligne
                if($entity['type']=='curve')
                {
                    $xy_start_sketch = GetSketchPosition($entity['start']);
                    $xy_end_sketch = GetSketchPosition($entity['end']);

                    $pdf->Line($xy_start_sketch['x'],$xy_start_sketch['y'], $xy_end_sketch['x'], $xy_end_sketch['y']);           
                }//fin boucle des lignes
                //curve
                
                
                
            }//fin boucles des 
        }
    }//Fin dessin de tout les traits  
}
//Sortie du Pdf
$pdf->Output('test.pdf', 'I');








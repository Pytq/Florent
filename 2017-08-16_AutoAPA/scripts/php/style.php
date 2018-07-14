<?php
//Valeurs enregsitrée pour chaques layer
//nom, nom_var, nb_case, type
$arr_params = [
            ['nom'=> 'Odre', 'var_name' => 'layer_order', 'box_size'=> 2, 'type'=>0],
            ['nom'=> 'Nom layer', 'var_name' => 'layer_name', 'box_size'=> 16, 'type'=>'trim'],
            ['nom'=> 'Epaisseur bord (mm)', 'var_name' => 'edge_thickness', 'box_size'=> 3, 'type'=>'round'],
            ['nom'=> 'Style bord C (on,off,on,..) (mm)', 'var_name' => 'edge_style', 'box_size'=> 9, 'type'=>0],
            ['nom'=> 'Couleur bord (r,g,b)', 'var_name' => 'edge_color', 'box_size'=> 9, 'type'=>0],
            
            ['nom'=> 'Couleur surface (r,g,b)', 'var_name' => 'face_color', 'box_size'=> 3, 'type'=>0],
            
            ['nom'=> 'Epaisseur hach 1 (mm)', 'var_name' => 'hatch1_thickness', 'box_size'=> 3, 'type'=>'round'],
            ['nom'=> 'Style hach 1 (on,off,on,..) (mm)', 'var_name' => 'hatch1_style', 'box_size'=> 9, 'type'=>0],
            ['nom'=> 'Couleur hach 1 (r,g,b)', 'var_name' => 'hatch1_color', 'box_size'=> 9, 'type'=>0],
            ['nom'=> 'Spacing hach 1 (mm)', 'var_name' => 'hatch1_spacing', 'box_size'=> 3, 'type'=>0],
            ['nom'=> 'Angle hach 1 (°)', 'var_name' => 'hatch1_angle', 'box_size'=> 3, 'type'=>0],
            ['nom'=> 'Phase hach 1 (mm)', 'var_name' => 'hatch1_phase', 'box_size'=> 3, 'type'=>0],

            
            ['nom'=> 'Epaisseur hach 2 (mm)', 'var_name' => 'hatch2_thickness', 'box_size'=> 3, 'type'=>'round'],
            ['nom'=> 'Style hach 2 (on,off,on,..) (mm)', 'var_name' => 'hatch2_style', 'box_size'=> 9, 'type'=>0],
            ['nom'=> 'Couleur hach 2 (r,g,b)', 'var_name' => 'hatch2_color', 'box_size'=> 9, 'type'=>0],
            ['nom'=> 'Spacing hach 2 (mm)', 'var_name' => 'hatch2_spacing', 'box_size'=> 3, 'type'=>0],
            ['nom'=> 'Angle hach 2 (°)', 'var_name' => 'hatch2_angle', 'box_size'=> 3, 'type'=>0],
            ['nom'=> 'Phase hach 2 (mm)', 'var_name' => 'hatch2_phase', 'box_size'=> 3, 'type'=>0],

            ['nom'=> 'Taille textes (mm)', 'var_name' => 'text_size', 'box_size'=> 3, 'type'=>'round']           
            ];


//Recuperation des valeurs enregsitrée

$dir ='../../data/styles.json';
if(file_exists($dir)){$styles = json_decode(file_get_contents($dir), TRUE);}
else{$styles=[];}




//Si transmission d'un formulaire POST, traitement de celui-ci
if(count($_POST)>0)
{
    foreach($_POST[$arr_params[1]['var_name']] AS $id_layer => $layer_name)
    {
        $layer_name = trim($layer_name);
        //si pas de nom, on regarde si le layer est dans le JSON
        if($layer_name=='')
        {   
            if(isset($styles[$id_layer])){unset($styles[$id_layer]);} //Si existante, supression
        }
        //Si le layer a un nom, on l'enregistre & met a jour
        else
        {
            $styles[$id_layer] = [];
            foreach($arr_params AS $param)
            {
                $value = $_POST[$param['var_name']][$id_layer];
                if($param['type']==='round'){$value = round($value, 2);}
                elseif($param['type']==='trim'){$value = trim($value);}
                
                $styles[$id_layer][$param['var_name']] = $value;
            }
        }
    }
    
    //trie selon position
    function SortStyle($style_1, $style_2)
    {
        if ($style_1['layer_order'] == $style_2['layer_order']) {return 0;}
        return ($style_1['layer_order'] < $style_2['layer_order']) ? -1 : 1;
    }
    usort($styles, "SortStyle");
    
    $styles = array_values($styles);
    
    
    //enregistrement
    file_put_contents($dir, json_encode($styles));
}

//Affichage du formulaire

//Tete de colonne
echo '
<html>
<style type="text/css">html{font: 14px "Arial";} td {font: 10px "Arial";}</style>
<h2>Gestionaire de style</h3>
<form method="POST" action=""?>
    <input type="submit" value="update">
    <table>
        <tr bgcolor="#70B2E0">';
        
        foreach($arr_params AS $param)
        {
         echo '<td>',$param['nom'],'</td>
         ';
        }
        echo'</tr>';
        

// Une ligne par layer existant   
$id_layer = -1;    
foreach($styles AS $id_layer => $layer)
{
        echo '<tr bgcolor="#C3DCEE">';
        
        foreach($arr_params AS $param)
        {
            if($param['var_name']=='layer_order'){$layer[$param['var_name']]= $id_layer;}
            echo '<td><input type="text" name="',$param['var_name'],'[',$id_layer,']" value="',$layer[$param['var_name']],'" size="',$param['box_size'],'"/></td>
            ';
        }
        echo'</tr>'; 
}

//Ajout de 3 ligne vide suplémentaire        
for($i=0; $i<3; $i++)
{
    $id_layer ++;
    
        echo '<tr bgcolor="#C3DCEE">';
        
        foreach($arr_params AS $param)
        {
                
         echo '<td><input type="text" name="',$param['var_name'],'[',$id_layer,']" size="',$param['box_size'],'" ';
         if($param['var_name']=='layer_order'){echo 'value="',$id_layer,'"';}
         echo'/></td>
         ';
        }
        echo'</tr>'; 
}
    echo' 
    </table>
    <input type="submit" value="update">
</form>
';


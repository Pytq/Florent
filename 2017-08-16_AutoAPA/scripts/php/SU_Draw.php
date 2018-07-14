<?php
class SU_Draw extends RPDF
{
    function DrawCurve($pt_array, $isLooped, $style = null, $l = 0.167)
    {
        if($isLooped)
        {
            array_push($pt_array, $pt_array[1]);
            array_unshift($pt_array, $pt_array[count($pt_array)-3]);
        }
        else
        {
            array_unshift($pt_array, $pt_array[0]);
            array_push($pt_array, $pt_array[count($pt_array)-1]);
        }
    
        for($i=1; $i<count($pt_array)-2; $i++)
        {
            $this->Curve(   $pt_array[$i]['x'], $pt_array[$i]['y'],
                            $pt_array[$i]['x'] + $l*($pt_array[$i+1]['x'] - $pt_array[$i-1]['x']), $pt_array[$i]['y'] + $l*($pt_array[$i+1]['y'] - $pt_array[$i-1]['y']),
                            $pt_array[$i+1]['x'] - $l*($pt_array[$i+2]['x'] - $pt_array[$i]['x']), $pt_array[$i+1]['y'] - $l*($pt_array[$i+2]['y'] - $pt_array[$i]['y']),
                            $pt_array[$i+1]['x'], $pt_array[$i+1]['y']);
        }
    }
    
    function Hashures($pt_array, $ec0, $ec1, $angle)
    {
        global $pdf;
          $eps = 0.001;
          $h = $pdf->GetPageHeight()**2;
          $w = $pdf->GetPageWidth()**2;
          $jmax =  2*sqrt(($h + $w)*(1+$h/$w))/($ec0 + $ec1)+2;
          array_push($pt_array, $pt_array[0]);
          for($j=0; $j<2*$jmax; $j++) {
              $shift = $ec0 * ($j % 2) + ($ec0 + $ec1) * floor(($j-$jmax) / 2);
              $x0 = $shift*cos($angle);
              $x1 = $shift*cos($angle) - sin($angle);
              $y0 = $shift*sin($angle);
              $y1 = $shift*sin($angle) + cos($angle);
              $inter = [];
              for($i=0; $i<count($pt_array)-1; $i++) {
                  $x0p = $pt_array[$i][0];
                  $x1p = $pt_array[$i+1][0];
                  $y0p = $pt_array[$i][1];
                  $y1p = $pt_array[$i+1][1];
                  $a = $x1 - $x0;
                  $b = - $x1p + $x0p;
                  $c = $y1 - $y0;
                  $d = - $y1p + $y0p;
                  $e = - $x0 + $x0p;
                  $f = - $y0 + $y0p;
                  $det = ($a*$d - $b*$c);
                  if($det != 0) {
                      $u = ($d*$e - $b*$f)/$det;
                      $v = ($a*$f - $c*$e)/$det;
                      $x = $x0 + ($x1 - $x0)*$u;
                      $y = $y0 + ($y1 - $y0)*$u;
                      if($x > min($x0p, $x1p) - $eps AND $x < max($x0p, $x1p) + $eps) {
                          $isALreadyIn = false;
                          foreach ($inter as $key => $value) {
                              if(abs($x -$value[0]) < $eps AND abs($y -$value[1]) < $eps) {
                                  $isALreadyIn = true;
                              }
                          }
                          if(!$isALreadyIn) {
                              array_push($inter, [$x,$y]);
                          }
                      }
                  }
              }
              $inter = array_intersect_key($inter, array_unique(array_map('serialize', $inter)));
              if(count($inter) > 0) {
                  if(count($inter) == 1) {
                      $pdf->Circle($inter[array_keys[0]][0], $inter[array_keys[0]][1], 2);
                  }
                  elseif(count($inter) == 2) {
                      $pdf->Line($inter[array_keys($inter)[0]][0], $inter[array_keys($inter)[0]][1], $inter[array_keys($inter)[1]][0], $inter[array_keys($inter)[1]][1]);
                  }
                  else {
                      print_r(array_map('serialize', $inter));
                  }
              }
          }
      }
}
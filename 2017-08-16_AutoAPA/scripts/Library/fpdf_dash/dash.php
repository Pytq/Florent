<?php

class PDF_Dash extends FPDF
{
	function SetDash($black=null, $white=null, $red, $blue)
	{
		if($black!==null)
			$s=sprintf('[%.3F %.3F %.3F %.3F] 0 d',$black*$this->k,$white*$this->k,$red*$this->k,$blue*$this->k);
		else
			$s='[] 0 d';
		$this->_out($s);
	}
}
?>

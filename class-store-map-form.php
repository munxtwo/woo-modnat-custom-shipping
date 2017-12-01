<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Used to construct the form for the store map.
 */
class StoreMapForm {
	public $ServiceURL = '';
	public $PostParams = array();

	/*
	 * Empty constructor.
	 */
	public function __construct() {}

	/*
	 * Constructs the form html for the store map button.
	 */
	public function StoreMap($ButtonDesc = 'Select a store', $Target = 'mapForm') {
		return $this->GenPostHTML($ButtonDesc, $Target);
	}

	/*
	 * Helper function for adding next line.
	 */
	private function AddNextLine($Content) {
		return $Content . PHP_EOL;
	}

	/*
	 * Constructs the form html for the store map button.
	 */
	private function GenPostHTML($ButtonDesc = 'Select a store', $Target = '_self') {
		$PostHTML = $this->AddNextLine('<div style="text-align:center;">');
		$PostHTML .= $this->AddNextLine('  <form id="mapFormId" method="POST" action="' . $this->ServiceURL . '" target="' . $Target . '">');
		foreach ($this->PostParams as $Name => $Value) {
			$PostHTML .= $this->AddNextLine('    <input type="hidden" id="' . $Name . '" name="' . $Name . '" value="' . $Value . '" />');
		}
		if (!empty($ButtonDesc)) {
			$PostHTML .= $this->AddNextLine('    <input type="submit" id="__paymentButton" value="' . $ButtonDesc . '" />');
		} else {
			$PostHTML .= $this->AddNextLine('    <script>document.getElementById("'. $Target .'").submit();</script>');
		}
		$PostHTML .= $this->AddNextLine('  </form>');
		$PostHTML .= $this->AddNextLine('</div>');

		return $PostHTML;
	}
}
?>

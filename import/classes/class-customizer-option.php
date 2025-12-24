<?php
final class Customizer_Option extends WP_Customize_Setting {
	public function import( $value ) {
		$this->update( $value );
	}
}

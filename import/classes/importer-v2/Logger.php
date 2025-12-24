<?php

class Logger extends WPImporterLoggerCLI {
	public $error_output = '';

	public function log( $level, $message, array $context = array() ) {
		$this->error_output( $level, $message, $context = array() );

		if ( $this->level_to_numeric( $level ) < $this->level_to_numeric( $this->min_level ) ) {
			return;
		}
	}

	public function error_output( $level, $message, array $context = array() ) {
		if ( $this->level_to_numeric( $level ) < $this->level_to_numeric( 'error' ) ) {
			return;
		}

		$this->error_output .= sprintf( '[%s] %s<br>', strtoupper( $level ), $message );
	}
}

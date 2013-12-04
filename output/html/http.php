<?php
/*

© 2013 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Html_HTTP extends QM_Output_Html {

	public function output() {

		$data = $this->component->get_data();

		$total_time = 0;

		echo '<div class="qm" id="' . $this->component->id() . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'HTTP Request', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Response', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Transport', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Call Stack', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Component', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Timeout', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Time', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		if ( !empty( $data['http'] ) ) {

			echo '<tbody>';

			foreach ( $data['http'] as $row ) {
				$funcs = array();

				if ( isset( $row['response'] ) ) {

					$ltime = ( $row['end'] - $row['start'] );
					$total_time += $ltime;
					$stime = number_format_i18n( $ltime, 4 );
					$ltime = number_format_i18n( $ltime, 10 );

					if ( is_wp_error( $row['response'] ) ) {
						$response = $row['response']->get_error_message();
						$css      = 'qm-warn';
					} else {
						$response = wp_remote_retrieve_response_code( $row['response'] );
						$msg      = wp_remote_retrieve_response_message( $row['response'] );
						$css      = '';

						if ( empty( $response ) )
							$response = __( 'n/a', 'query-monitor' );
						else
							$response = esc_html( $response . ' ' . $msg );

						if ( intval( $response ) >= 400 )
							$css = 'qm-warn';

					}

				} else {

					# @TODO test if the timeout has actually passed. if not, the request was erroneous rather than timed out

					$total_time += $row['args']['timeout'];

					$ltime    = '';
					$stime    = number_format_i18n( $row['args']['timeout'], 4 );
					$response = __( 'Request timed out', 'query-monitor' );
					$css      = 'qm-warn';

				}

				$method = $row['args']['method'];
				if ( !$row['args']['blocking'] )
					$method .= '&nbsp;' . _x( '(non-blocking)', 'non-blocking HTTP transport', 'query-monitor' );
				$url = str_replace( array(
					'=',
					'&',
					'?',
				), array(
					'<span class="qm-param">=</span>',
					'<br /><span class="qm-param">&amp;</span>',
					'<br /><span class="qm-param">?</span>',
				), $row['url'] );

				if ( isset( $row['transport'] ) )
					$transport = $row['transport'];
				else
					$transport = '';

				$stack = $row['trace']->get_stack();

				foreach ( $stack as & $trace ) {
					foreach ( array( 'WP_Http', 'wp_remote_', 'fetch_rss', 'fetch_feed', 'SimplePie', 'download_url' ) as $skip ) {
						if ( 0 === strpos( $trace, $skip ) ) {
							$trace = sprintf( '<span class="qm-na">%s</span>', $trace );
							break;
						}
					}
				}

				$component = QM_Util::get_backtrace_component( $row['trace'] );

				$stack = implode( '<br />', $stack );
				echo "
					<tr class='{$css}'>\n
						<td valign='top' class='qm-url qm-ltr'>{$method}<br/>{$url}</td>\n
						<td valign='top'>{$response}</td>\n
						<td valign='top'>{$transport}</td>\n
						<td valign='top' class='qm-ltr'>{$stack}</td>\n
						<td valign='top'>{$component->name}</td>\n
						<td valign='top'>{$row['args']['timeout']}</td>\n
						<td valign='top' title='{$ltime}'>{$stime}</td>\n
					</tr>\n
				";
			}

			echo '</tbody>';
			echo '<tfoot>';

			$total_stime = number_format_i18n( $total_time, 4 );
			$total_ltime = number_format_i18n( $total_time, 10 );

			echo '<tr>';
			echo '<td colspan="6">&nbsp;</td>';
			echo "<td title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';
			echo '</tfoot>';

		} else {

			echo '<tbody>';
			echo '<tr>';
			echo '<td colspan="7" style="text-align:center !important"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';
			echo '</tbody>';
		
		}

		echo '</table>';
		echo '</div>';

	}

}

function register_qm_http_output_html( QM_Output $output = null, QM_Component $component ) {
	return new QM_Output_Html_HTTP( $component );
}

add_filter( 'query_monitor_output_html_http', 'register_qm_http_output_html', 10, 2 );

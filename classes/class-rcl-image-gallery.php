<?php

class Rcl_Image_Gallery {

	public $id;
	public $attach_ids	 = array();
	public $image_urls	 = array();
	public $center_align = false;
	public $width		 = 1500;
	public $height		 = 350;
	public $slides		 = array();
	public $navigator	 = array();
	public $options		 = array();

	function __construct( $args ) {

		$this->init_properties( $args );

		if ( $this->attach_ids ) {

			$this->image_urls = $this->get_image_urls();
		}

		$defaultOptions = array(
			'$AutoPlay'		 => 0,
			//'$SlideWidth' => $this->gallery['thumbnail'][0],
			//'$SlideHeight' => $this->gallery['thumbnail'][1],
			'$FillMode'		 => 1,
			//'$DragOrientation' => 3,
			'$Idle'			 => 4000,
			'$SlideDuration' => 500
		);

		if ( $this->navigator ) {

			$defaultOptions['$UISearchMode'] = 0;

			if ( isset( $this->navigator['thumbnails'] ) ) {
				$defaultOptions['$ThumbnailNavigatorOptions'] = array(
					'$ChanceToShow'			 => 2,
					'$Loop'					 => 1,
					'$SpacingX'				 => 3,
					'$SpacingY'				 => 3,
					'$ArrowNavigatorOptions' => array(
						'$ChanceToShow'	 => 1,
						'$Steps'		 => 6
					)
				);
			}

			if ( isset( $this->navigator['arrows'] ) ) {
				$defaultOptions['$ArrowNavigatorOptions'] = array(
					'$ChanceToShow'	 => 1,
					'$Steps'		 => 6
				);
			}
		}

		$this->options = wp_parse_args( $this->options, $defaultOptions );
	}

	function init_properties( $args ) {

		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[$name] ) )
				$this->$name = $args[$name];
		}
	}

	function get_image_urls( $attach_ids = false ) {

		$attach_ids = $attach_ids ? $attach_ids : $this->attach_ids;

		if ( ! $attach_ids )
			return false;

		$images = array();
		foreach ( $attach_ids as $attach_id ) {

			$src = wp_get_attachment_image_src( $attach_id, $this->slides['slide'] );

			$images[$attach_id]			 = array();
			$images[$attach_id]['slide'] = $src[0];

			if ( $this->slides['full'] ) {
				$src						 = wp_get_attachment_image_src( $attach_id, $this->slides['full'] );
				$images[$attach_id]['full']	 = $src[0];
			}

			if ( isset( $this->navigator['thumbnails'] ) ) {
				$src						 = wp_get_attachment_image_src( $attach_id, array( $this->navigator['thumbnails']['width'], $this->navigator['thumbnails']['height'] ) );
				$images[$attach_id]['thumb'] = $src[0];
			}
		}

		return $images;
	}

	function get_gallery() {

		if ( ! $this->image_urls )
			return false;

		rcl_image_slider_scripts();

		$content = '<div class="rcl-slider-wrapper">';

		$content .= '<script>
			jQuery(document).ready(function ($) {

				var options = ' . json_encode( $this->options ) . ';
				' . (isset( $this->navigator['thumbnails'] ) ? 'options.$ThumbnailNavigatorOptions.$Class = $JssorThumbnailNavigator$;'
				. 'options.$ThumbnailNavigatorOptions.$ArrowNavigatorOptions.$Class = $JssorArrowNavigator$' : '') . '
				' . (isset( $this->navigator['arrows'] ) ? 'options.$ArrowNavigatorOptions.$Class = $JssorArrowNavigator$;' : '') . '
				//options.$ThumbnailNavigatorOptions.$Class = $JssorThumbnailNavigator$;
				//options.$ThumbnailNavigatorOptions.$ArrowNavigatorOptions.$Class = $JssorArrowNavigator$;
				var jssor_slider = new $JssorSlider$("' . $this->id . '", options);
				//console.log(options);
				function rcl_scale_slider() {

					var containerElement = jssor_slider.$Elmt.parentNode;
					var containerWidth = containerElement.clientWidth;
					console.log([containerElement,containerWidth]);
					if (containerWidth) {
						var expectedWidth = Math.min(containerWidth, jssor_slider.$OriginalWidth());
						jssor_slider.$ScaleSize(expectedWidth, jssor_slider.$OriginalHeight());
						' . ($this->center_align ? 'jssor_slider.$Elmt.style.left = ((containerWidth - expectedWidth) / 2) + "px";' : '') . '
					}
					else {
						window.setTimeout(rcl_scale_slider, 30);
					}
				}

				rcl_scale_slider();

				//$Jssor$.$AddEvent(window, "load", rcl_scale_slider);
				//$Jssor$.$AddEvent(window, "resize", rcl_scale_slider);
				//$Jssor$.$AddEvent(window, "orientationchange", rcl_scale_slider);

			});
		</script>';

		$content .= '<div id="' . $this->id . '" class="rcl-slider" style="position: relative; top: 0px; left: 0px; width: ' . $this->width . 'px; height: ' . (isset( $this->navigator['thumbnails'] ) ? $this->height + $this->navigator['thumbnails']['height'] + 10 : $this->height) . 'px; max-width: 100%; overflow: hidden;">';

		$content .= '<!-- Loading Screen -->
		<div data-u="loading" class="jssorl-009-spin" style="z-index:9;position:absolute;top:0px;left:0px;width:100%;height:100%;text-align:center;background-color:rgb(232, 232, 232);">
			<img style="margin-top:-19px;position:relative;top:50%;width:38px;height:38px;" src="' . plugins_url( '/assets/js/jssor.slider/svg/loading/static-svg/spin.svg', dirname( __FILE__ ) ) . '" />
		</div>';

		$content .= $this->get_slides();

		if ( isset( $this->navigator['thumbnails'] ) && count( $this->image_urls ) > 1 ) {
			$content .= $this->get_navigator_thumbnails();
		}

		if ( isset( $this->navigator['arrows'] ) ) {
			$content .= $this->get_navigator_arrows();
		}

		$content .= '</div>';

		$content .= '</div>';

		return $content;
	}

	function get_slides() {

		if ( ! $this->image_urls )
			return false;

		$content = '<!-- Slides Container -->
			<div data-u="slides" style="max-width: 100%; left: 0px; top: 0px; height: ' . $this->height . 'px; width: ' . $this->width . 'px; overflow: hidden;">';

		foreach ( $this->image_urls as $image ) {

			$content .= '<div>';

			$slide = '<img data-u="image" src="' . $image['slide'] . '" /></a>';

			if ( $this->slides['full'] ) {
				$content .= sprintf( '<a href="%s">%s</a>', $image['full'], $slide );
			} else {
				$content .= $slide;
			}

			if ( isset( $this->navigator['thumbnails'] ) ) {
				$content .= '<img data-u="thumb" src="' . $image['thumb'] . '" />';
			}

			$content .= '</div>';
		}

		$content .= '</div>';

		return $content;
	}

	function get_navigator_thumbnails() {

		$content = '<!-- region Thumbnail Navigator Skin Begin -->
			<style>
				.rcl-gallery-navigator {
					width: ' . $this->width . 'px;
					height: ' . $this->navigator['thumbnails']['height'] . 'px;
				}
				.rcl-gallery-navigator .i,
				.rcl-gallery-navigator .p {
					width: ' . $this->navigator['thumbnails']['width'] . 'px;
					height: ' . $this->navigator['thumbnails']['height'] . 'px;
				}
				.rcl-gallery-navigator .o {
					width: ' . ($this->navigator['thumbnails']['width'] - 2) . 'px;
					height: ' . ($this->navigator['thumbnails']['height'] - 2) . 'px;
				}
				* html .rcl-gallery-navigator .o {
					/* ie quirks mode adjust */
					width /**/: ' . $this->navigator['thumbnails']['width'] . 'px;
					height /**/: ' . $this->navigator['thumbnails']['height'] . 'px;
				}
			</style>
			<!-- thumbnail navigator container -->
			<div data-u="thumbnavigator" class="rcl-gallery-navigator" style="width: ' . $this->width . 'px; height: ' . $this->navigator['thumbnails']['height'] . 'px; left: 0px; bottom: 0px;">
				<!-- Thumbnail Item Skin Begin -->
				<div data-u="slides" style="cursor: default;">
					<div data-u="prototype" class="p">
						<div data-u="thumbnailtemplate" class="i"></div>
						<div class="o"></div>
					</div>
				</div>
				' . (isset( $this->navigator['thumbnails']['arrows'] ) ? $this->get_navigator_arrows() : '') . '
			</div>
			<!-- endregion Thumbnail Navigator Skin End -->';

		return $content;
	}

	function get_navigator_arrows() {

		$content = '<!-- Arrow Navigator -->
		<div data-u="arrowleft" class="rcl-navigator-arrow" style="width:40px;height:40px;top:123px;left:8px;" data-autocenter="2" data-scale="0.75" data-scale-left="0.75">
			<svg viewBox="0 0 16000 16000" style="position:absolute;top:0;left:0;width:100%;height:100%;">
				<polyline class="a" points="11040,1920 4960,8000 11040,14080 "></polyline>
			</svg>
		</div>
		<div data-u="arrowright" class="rcl-navigator-arrow" style="width:40px;height:40px;top:123px;right:8px;" data-autocenter="2" data-scale="0.75" data-scale-right="0.75">
			<svg viewBox="0 0 16000 16000" style="position:absolute;top:0;left:0;width:100%;height:100%;">
				<polyline class="a" points="4960,1920 11040,8000 4960,14080 "></polyline>
			</svg>
		</div>';

		return $content;
	}

}

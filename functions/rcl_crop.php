<?php

class Rcl_Crop {

    function get_crop($src_path, $width, $height, $dest){
        if ( extension_loaded('imagick') ){
            return $this->make_thumbnail_Imagick( $src_path, $width, $height, $dest );

        }
        if( extension_loaded('gd') ){
            return $this->make_thumbnail_GD( $src_path, $width, $height, $dest );
        }
    }

    private function make_thumbnail_Imagick( $src_path, $width, $height, $dest ){
            $image = new Imagick( $src_path );

            # Select the first frame to handle animated images properly
            if ( is_callable( array( $image, 'setIteratorIndex') ) )
                    $image->setIteratorIndex(0);

            // устанавливаем качество
            $format = $image->getImageFormat();
            if( $format == 'JPEG' || $format == 'JPG')
                    $image->setImageCompression( Imagick::COMPRESSION_JPEG );
            $image->setImageCompressionQuality( 85 );

            $h = $image->getImageHeight();
            $w = $image->getImageWidth();

            // если не указана одна из сторон задаем ей пропорциональное значение
            if( ! $width )
                    $width = round( $w*($height/$h) );
            if( ! $height )
                    $height = round( $h*($width/$w) );

            list( $dx, $dy, $wsrc, $hsrc ) = $this->crop_coordinates( $height, $h, $width, $w );

            // обрезаем оригинал
            $image->cropImage( $wsrc, $hsrc, $dx, $dy );
            $image->setImagePage( $wsrc, $hsrc, 0, 0);
            // Strip out unneeded meta data
            $image->stripImage();
            // уменьшаем под размер
            $image->scaleImage( $width, $height );

            $image->writeImage( $dest );
            chmod( $dest, 0755 );
            $image->clear();
            $image->destroy();

            return true;
	}

	#
	# ядро: создание и запись файла-картинки на основе библиотеки GD
	#
	private function make_thumbnail_GD( $src_path, $width, $height, $dest ){
            $size = @getimagesize( $src_path );

            if( $size === false )
                    return false; // не удалось получить параметры файла;

            $w = $size[0];
            $h = $size[1];
            $format = strtolower( substr( $size['mime'], strpos($size['mime'], '/')+1 ) );

            // Создаем ресурс картинки
            $image = @imagecreatefromstring( file_get_contents( $src_path ) );
            if ( ! is_resource( $image ) )
                    return false; // не получилось получить картинку

            // если не указана одна из сторон задаем ей пропорциональное значение
            if( ! $width )
                    $width = round( $w*($height/$h) );
            if( ! $height )
                    $height = round( $h*($width/$w) );

            // Создаем холст полноцветного изображения
            $thumb = imagecreatetruecolor( $width, $height );

            if ( function_exists('imagealphablending') && function_exists('imagesavealpha') ) {
                    imagealphablending( $thumb, false ); // режим сопряжения цвета и альфа цвета
                    imagesavealpha( $thumb, true ); // флаг сохраняющий прозрачный канал
            }
            if ( function_exists('imageantialias') )
                    imageantialias( $thumb, true ); // включим функцию сглаживания

            list( $dx, $dy, $wsrc, $hsrc ) = $this->crop_coordinates( $height, $h, $width, $w );

            if( ! imagecopyresampled( $thumb, $image, 0, 0, $dx, $dy, $width, $height, $wsrc, $hsrc ) )
                    return false; // не удалось изменить размер

            //
            // Сохраняем картинку
            if( $format == 'png'){
                    // convert from full colors to index colors, like original PNG.
                    if ( function_exists('imageistruecolor') && ! imageistruecolor( $thumb ) ){
                            imagetruecolortopalette( $thumb, false, imagecolorstotal( $thumb ) );
                    }
                    imagepng( $thumb, $dest );
            }
            elseif( $format == 'gif'){
                    imagegif( $thumb, $dest );
            }
            else {
                    imagejpeg( $thumb, $dest, 85 );
            }
            chmod( $dest, 0755 );
            imagedestroy($image);
            imagedestroy($thumb);

            return true;
	}

	# координаты кадрирования
	# Вернет массив: отступ по Х и Y и сколько пикселей считывать по высоте и ширине у источника
	# $height (необходимая высота), $h (оригинальная высота), $width, $w
	private function crop_coordinates( $height, $h, $width, $w ){
            // Определяем необходимость преобразования размера так чтоб вписывалась наименьшая сторона
            // if( $width<$w || $height<$h )
                    $ratio = max( $width/$w, $height/$h );

            $dx = $dy = 0;

            //срезать справа и слева
            if( $height/$h > $width/$w )
                    $dx = round( ($w - $width*$h/$height)/2 ); //отступ слева у источника
            else // срезать верх и низ
                    $dy = round( ($h - $height*$w/$width)/2 ); // $height*$w/$width)/2*6/10 - отступ сверху у источника *6/10 - чтобы для вертикальных фоток отступ сверху был не половина а процентов 30

            // сколько пикселей считывать c источника
            $wsrc = round( $width/$ratio );
            $hsrc = round( $height/$ratio );

            return array( $dx, $dy, $wsrc, $hsrc );
	}
}

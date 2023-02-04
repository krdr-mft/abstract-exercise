<?php

namespace Abstract\Util;

class FileGetContentsWrapper
{
    public function fileGetContents( string $filename )
    {
        if(!file_exists($path))
        {
            throw new Exception(sprintf('Couldnt read file %s', $path) );
        }

        return file_get_contents( $filename );
    }
}
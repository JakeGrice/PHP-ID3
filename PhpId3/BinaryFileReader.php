<?php

namespace PhpId3;

/**
 * A simple class to read variable byte length binary data.
 * This is basically is a better replacement for unpack() function
 * which creates a very large associative array.
 *
 * @author Shubham Jain <shubham.jain.1@gmail.com>
 * @example https://github.com/shubhamjain/PHP-ID3
 * @license MIT License
 */
class BinaryFileReader
{
    /**
     * size of block depends upon the variable defined in the next array element.
     */

    const SIZE_OF = 1;

    /**
     * Block is read until NULL is encountered.
     */
    const NULL_TERMINATED = 2;

    /**
     * Block is read until EOF  is encountered.
     */
    const EOF_TERMINATED = 3;

    /**
     * Block size is fixed.
     */
    const FIXED = 4;

    /**
     * Datatypes to transform the read block
     */
    const INT = 5;
    const FLOAT = 6;

    /**
     * file handle to read data
     */
    private $fp;

    /**
     * Associative array of Varaibles and their info ( TYPE, SIZE, DATA_TYPE)
     * In special cases it can be an array to handle different types of block data lengths
     */
    private $map;

    public function __construct($fp, array $map)
    {
        $this->fp = $fp;
        $this->setMap($map);
    }

    public function setMap($map)
    {
        $this->map = $map;

        foreach ($this->map as $key => $size) {
            //Create property from keys of $map
            $this->$key = null;
        }
    }

    public function read()
    {
        if (feof($this->fp)) {
            return false;
        }

        foreach ($this->map as $key => $info) {
            switch ($info[0]) {
                case self::NULL_TERMINATED:
                    while ((int) bin2hex(($ch = fgetc($this->fp))) !== 0) {
                        $this->$key .= $ch;
                    }
                    break;

                case self::EOF_TERMINATED:
                    while (!feof($this->fp)) {
                        $this->$key .= fgetc($this->fp);
                    }
                    break;
                case self::SIZE_OF:
                    //If the variable is not an integer return false
                    if (!( $info[1] = $this->$info[1] )) {
                        return false;
                    }
                default:
                    //Read as string
                    $this->$key = fread($this->fp, $info[1]);
            }

            if (isset($info[2])) {
                switch ($info[2]) {
                    case self::INT:
                        $this->$key = intval(bin2hex($this->$key), 16);
                        break;
                    case self::FLOAT:
                        $this->$key = floatval(bin2hex($this->$key), 16);
                        break;
                }
            }
            $this->$key = ltrim($this->$key, "\0x");
        }
        return $this;
    }
}

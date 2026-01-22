<?php
/**
 * Clase para convertir números a letras (Español)
 */
class NumeroALetras
{
    private static $UNIDADES = [
        '',
        'UN ',
        'DOS ',
        'TRES ',
        'CUATRO ',
        'CINCO ',
        'SEIS ',
        'SIETE ',
        'OCHO ',
        'NUEVE ',
        'DIEZ ',
        'ONCE ',
        'DOCE ',
        'TRECE ',
        'CATORCE ',
        'QUINCE ',
        'DIECISEIS ',
        'DIECISIETE ',
        'DIECIOCHO ',
        'DIECINUEVE ',
        'VEINTE '
    ];

    private static $DECENAS = [
        'VENTI',
        'TREINTA ',
        'CUARENTA ',
        'CINCUENTA ',
        'SESENTA ',
        'SETENTA ',
        'OCHENTA ',
        'NOVENTA ',
        'CIEN '
    ];

    private static $CENTENAS = [
        'CIENTO ',
        'DOSCIENTOS ',
        'TRESCIENTOS ',
        'CUATROCIENTOS ',
        'QUINIENTOS ',
        'SEISCIENTOS ',
        'SETECIENTOS ',
        'OCHOCIENTOS ',
        'NOVECIENTOS '
    ];

    public static function convertir($number, $moneda = 'PESOS', $centimos = 'CENTAVOS')
    {
        $converted = '';
        $decimales = '';

        if (($number < 0) || ($number > 999999999)) {
            return 'No es posible convertir el número en letras';
        }

        $div_decimales = explode('.', $number);
        if (count($div_decimales) > 1) {
            $number = $div_decimales[0];
            $decValue = substr($div_decimales[1], 0, 2);
            if (strlen($decValue) == 1)
                $decValue .= '0';
            $decimales = $decValue . '/100 M.N.';
        } else {
            $decimales = '00/100 M.N.';
        }

        if (intval($number) == 0) {
            return 'CERO ' . $moneda . ' ' . $decimales;
        }

        $converted = self::getLetras($number);

        return $converted . $moneda . ' ' . $decimales;
    }

    private static function getLetras($number)
    {
        $number = intval($number);
        if ($number == 0)
            return '';

        if ($number == 100)
            return 'CIEN ';

        if ($number < 21) {
            return self::$UNIDADES[$number];
        }

        if ($number < 30) {
            return 'VEINTI' . self::getLetras($number - 20);
        }

        if ($number < 100) {
            $decena = intval($number / 10);
            $unidad = $number % 10;
            if ($unidad > 0) {
                return self::$DECENAS[$decena - 2] . 'Y ' . self::getLetras($unidad);
            } else {
                return self::$DECENAS[$decena - 2];
            }
        }

        if ($number < 1000) {
            $centena = intval($number / 100);
            $resto = $number % 100;
            return self::$CENTENAS[$centena - 1] . self::getLetras($resto);
        }

        if ($number < 1000000) {
            $miles = intval($number / 1000);
            $resto = $number % 1000;
            $letraMiles = ($miles == 1) ? 'MIL ' : self::getLetras($miles) . 'MIL ';
            return $letraMiles . self::getLetras($resto);
        }

        if ($number < 1000000000) {
            $millones = intval($number / 1000000);
            $resto = $number % 1000000;
            $letraMillones = ($millones == 1) ? 'UN MILLON ' : self::getLetras($millones) . 'MILLONES ';
            return $letraMillones . self::getLetras($resto);
        }
    }
}

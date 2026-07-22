<?php

namespace App\Utils;
use DateTime;
use Exception;

class   ViewClasses
{
    /**
     * Método responsável por retornar a url do projeto
     * @param mixed $url
     * @return string
     */
    public static function url($url = null): string
    {
        $url_server = filter_input(INPUT_SERVER, 'SERVER_NAME');
        $base = 'https://' . rtrim($url_server, '/');
        $path = ltrim((string)$url, '/');
        return $url ? "$base/$path" : $base;
    }

    public static function csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function csrf_field() {
        $code = self::csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . $code . '">';
    }
    /**
     * Método responsável por retornar um slug do texto
     * @param string $string
     * @return string
     */
    public static function slug(string $string): string
    {
        if (class_exists('Transliterator')) {
            $string = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove; Lower()', $string);
        } else {
            $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
            $string = strtolower($string);
        }

        $string = preg_replace('/[^a-z0-9]+/i', '-', $string); // Substitui qualquer caractere não alfanumérico por hífen
        $string = trim($string, '-'); // Remove hifens do início e fim
        return $string;
    }

    /**
     * Método responsável por retornar uma sentença do dia de hoje
     * por exemplo: segunda-feira, 7 de abril de 2026
     * @return string
     */
    public static function dayNow(): string
    {
        $dayMounth = date("d");
        $dayWeek = date("w");
        $mounth = date("m") - 1;
        $year = date("Y");

        $week_names = [
            "domingo", "segunda-feira", "terça-feira", "quarta-feira", "quinta-feira", "sexta-feira", "sábado"
        ];

        $mounth_name = [
            "janeiro", "fevereiro", "março", "abril", "maio", "junho",
            "julho", "agosto", "setembro", "outubro", "novembro", "dezembro"
        ];

        return $week_names[$dayWeek] . ', ' . $dayMounth . ' de ' . $mounth_name[$mounth] . ' de ' . $year;
    }

    /**
     * Método responsável por retornar uma saudação ao usuário de acordo com a hora
     * @return string
     */
    public static function greeting(): string
    {
        // Obter a hora atual (considerando fuso horário)
        $time = (int) date('H');
        
        // Determinar a saudação com base no horário
        $greeting = match(true) {
            $time >= 5 && $time < 13 => 'Bom dia ',
            $time >= 13 && $time < 18 => 'Boa tarde ',
            $time >= 18 && $time <= 23 => "Boa noite ",
            default => 'Boa madrugada!',
        };
        
        // Garantir que a saudação seja segura para output
        return htmlspecialchars($greeting, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Método responsável por contar o tempo corrido de uma data especifica
     * @param string $date
     * @return string
     */
    public static function countTime(string $date): string
    {
        $now = new DateTime();
        $past = new DateTime($date);
        $diff = $now->diff($past);

        if ($diff->y > 0) {
            return $diff->y === 1 ? 'há 1 ano' : "há {$diff->y} anos";
        }

        if ($diff->m > 0) {
            return $diff->m === 1 ? 'há 1 mês' : "há {$diff->m} meses";
        }

        if ($diff->d > 7) {
            $weeks = floor($diff->d / 7);
            return $weeks === 1 ? 'há 1 semana' : "há {$weeks} semanas";
        }

        if ($diff->d > 1) {
            return "há {$diff->d} dias";
        }

        if ($diff->d === 1) {
            return "ontem";
        }

        if ($diff->h > 0) {
            return $diff->h === 1 ? 'há 1 hora' : "há {$diff->h} horas";
        }

        if ($diff->i > 0) {
            return $diff->i === 1 ? 'há 1 minuto' : "há {$diff->i} minutos";
        }

        return "agora!";
    }
    


    /**
     * Método responsável por calcular os dias 
     * @param mixed $daybegin
     * @param mixed $previewsDayEnd
     * @param mixed $dayEnd
     * @return string
     */
    public static function calcDaysAway($daybegin, $previewsDayEnd = null, $dayEnd = null)
    {
        if (empty($daybegin)) {
            return "Data de início ausente";
        }

        try {
            $inicio = $daybegin instanceof DateTime ? $daybegin : new DateTime($daybegin);
        } catch (Exception $e) {
            return "Data de início inválida";
        }

        if (!empty($dayEnd)) {
            try {
                $final = $dayEnd instanceof DateTime ? $dayEnd : new DateTime($dayEnd);
            } catch (Exception $e) {
                return "Data de fim inválida";
            }
        } else {
            $final = new DateTime();
        }

        $dias = $inicio->diff($final)->days + 1;
        $textoDias = $dias === 1 ? "1 dia" : "$dias dias";

        if (!empty($previewsDayEnd)) {
            try {
                $retornoPrevisto = $previewsDayEnd instanceof DateTime
                    ? $previewsDayEnd
                    : new DateTime($previewsDayEnd);

                $diasPrevistos = $inicio->diff($retornoPrevisto)->days + 1;

                if ($dias > $diasPrevistos) {
                    $excedente = $dias - $diasPrevistos;
                    $textoExcedente = $excedente === 1 ? "excedido em 1 dia" : "excedido em $excedente dias";
                    return "$textoDias ($textoExcedente)";
                }
            } catch (Exception $e) {
                // ignora data prevista inválida
            }
        }

        return $textoDias;
    }

    public static function resume(string $text,int $limite,string $continue ='...'):string
    {   
        /**
         * @function mb_strlen() - Verifica o tamanho de um texto
         * @function trim() - Limpa os espaços antes e depois do texto
         * @function mb_substr - Limita a quantidade de exibida com start e and de um texto
         * @function strip_tags - Limpa tags de programação de um texto
         * 
         * A linha a baixo verifica se o texto é maior que o limite defindo
         * Se for maior aplica uma limitação no texto e retorna o pedaço do texto com o limite definido
         * Em seguida faz uma limpeza no texto para que nenhuma tag seja passada junto com o texto.
         */
        $text = mb_strlen(trim($text)) > $limite ? mb_substr($text,0, $limite) : $text;
        $text = strip_tags($text);

        return $text.' '.$continue;
    }




    
}
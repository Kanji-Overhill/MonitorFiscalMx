<?php

namespace App\Enums;

/**
 * Estados fiscales que el sistema puede devolver para una consulta de RFC.
 * No usar calificativos como "seguro" o "confiable": ver README para el criterio editorial.
 */
enum FiscalStatus: string
{
    case SinCoincidencia = 'sin_coincidencia';
    case Presunto = 'presunto';
    case Definitivo = 'definitivo';
    case Desvirtuado = 'desvirtuado';
    case SentenciaFavorable = 'sentencia_favorable';
    case RfcInvalido = 'rfc_invalido';
    case FuenteNoDisponible = 'fuente_no_disponible';
    case ErrorConsulta = 'error_consulta';

    public function label(): string
    {
        return match ($this) {
            self::SinCoincidencia => 'Sin coincidencias en los listados consultados.',
            self::Presunto => 'Presunto',
            self::Definitivo => 'Definitivo',
            self::Desvirtuado => 'Desvirtuado',
            self::SentenciaFavorable => 'Sentencia favorable',
            self::RfcInvalido => 'RFC inválido',
            self::FuenteNoDisponible => 'Fuente del SAT no disponible',
            self::ErrorConsulta => 'Error al consultar',
        };
    }

    public function isMatch(): bool
    {
        return in_array($this, [
            self::Presunto,
            self::Definitivo,
            self::Desvirtuado,
            self::SentenciaFavorable,
        ], true);
    }

    public static function fromClassification(string $classification): self
    {
        return self::tryFrom($classification) ?? self::ErrorConsulta;
    }
}

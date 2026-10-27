<?php

namespace App\Support;

use App\Models\Consulta;
use App\Models\Medico;
use App\Models\Sala;
use Carbon\Carbon;

/**
 * Regras de agenda compartilhadas por agendar, reagendar e listar horários livres.
 * Datas no formato "AAAA-MM-DD" e horas "HH:MM".
 */
class AgendaClinica
{
    /** Horários de início de consulta do dia, conforme config/clinica.php (vazio se a clínica fecha). */
    public static function horariosDoDia(string $data): array
    {
        $dia = Carbon::createFromFormat('Y-m-d', $data);
        $expediente = config('clinica.expediente')[$dia->dayOfWeekIso] ?? null;

        if (!$expediente) {
            return [];
        }

        $passo = (int) config('clinica.intervalo_minutos');
        $minutos = fn (string $hhmm) => (int) substr($hhmm, 0, 2) * 60 + (int) substr($hhmm, 3, 2);

        $horarios = [];
        for ($m = $minutos($expediente[0]); $m + $passo <= $minutos($expediente[1]); $m += $passo) {
            $horarios[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
        }

        return $horarios;
    }

    public static function dentroDoExpediente(string $data, string $hora): bool
    {
        return in_array($hora, self::horariosDoDia($data), true);
    }

    public static function jaPassou(string $data, string $hora): bool
    {
        return Carbon::createFromFormat('Y-m-d H:i', "{$data} {$hora}")->lt(now());
    }

    /**
     * Horários em que o médico pode receber uma consulta: dentro do expediente, no futuro,
     * sem outra consulta do médico (nem do paciente, se informado) e com ao menos uma sala livre.
     *
     * @param int|null $ignorarConsultaId consulta que está sendo reagendada (o horário dela não conta como ocupado)
     */
    public static function livres(Medico $medico, string $data, ?int $pacienteId = null, ?int $ignorarConsultaId = null): array
    {
        $ativas = Consulta::where('data', $data)
            ->where('status', '!=', 'cancelada')
            ->when($ignorarConsultaId, fn ($q) => $q->where('id', '!=', $ignorarConsultaId))
            ->get(['medico_id', 'paciente_id', 'sala_id', 'hora']);

        $totalSalas = Sala::count();

        return array_values(array_filter(
            self::horariosDoDia($data),
            function (string $hora) use ($ativas, $medico, $pacienteId, $data, $totalSalas) {
                if (self::jaPassou($data, $hora)) {
                    return false;
                }

                $naHora = $ativas->filter(fn ($c) => substr($c->hora, 0, 5) === $hora);

                if ($naHora->contains('medico_id', $medico->id)) {
                    return false;
                }
                if ($pacienteId && $naHora->contains('paciente_id', $pacienteId)) {
                    return false;
                }

                return $naHora->pluck('sala_id')->unique()->count() < $totalSalas;
            }
        ));
    }

    /** Ids das salas com consulta (não cancelada) no dia e hora. */
    public static function salasOcupadas(string $data, string $hora, ?int $ignorarConsultaId = null, bool $travar = false): array
    {
        return Consulta::where('data', $data)
            ->where('hora', 'like', "{$hora}%")
            ->where('status', '!=', 'cancelada')
            ->when($ignorarConsultaId, fn ($q) => $q->where('id', '!=', $ignorarConsultaId))
            ->when($travar, fn ($q) => $q->lockForUpdate())
            ->pluck('sala_id')
            ->all();
    }

    /** Sala livre no horário: a preferida, se estiver livre, ou a primeira disponível. */
    public static function salaLivre(string $data, string $hora, ?int $preferidaId = null, ?int $ignorarConsultaId = null, bool $travar = false): ?Sala
    {
        $ocupadas = self::salasOcupadas($data, $hora, $ignorarConsultaId, $travar);

        if ($preferidaId && !in_array($preferidaId, $ocupadas, true) && ($sala = Sala::find($preferidaId))) {
            return $sala;
        }

        return Sala::whereNotIn('id', $ocupadas)->orderBy('id')->first();
    }
}

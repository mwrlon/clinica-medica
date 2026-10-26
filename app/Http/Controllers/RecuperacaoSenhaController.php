<?php

namespace App\Http\Controllers;

use App\Models\Medico;
use App\Models\Paciente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Fluxo "Esqueci minha senha" para pacientes e médicos.
 *
 * O e-mail é enviado pelo mailer configurado no .env (MAIL_MAILER=log grava o
 * conteúdo em storage/logs/laravel.log, útil em desenvolvimento).
 */
class RecuperacaoSenhaController extends Controller
{
    /** Validade do link, em minutos. */
    private const VALIDADE_MINUTOS = 60;

    private const MODELS = [
        'paciente' => Paciente::class,
        'medico'   => Medico::class,
    ];

    public function enviarLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input('email');

        foreach (self::MODELS as $tipo => $model) {
            $usuario = $model::where('email', $email)->first();

            if (!$usuario || ($tipo === 'medico' && $usuario->status !== 'ativo')) {
                continue;
            }

            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email, 'tipo' => $tipo],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $link = route('senha.redefinir', ['token' => $token, 'email' => $email, 'tipo' => $tipo]);

            $corpo = "Olá, {$usuario->nome}.\n\n"
                . "Recebemos um pedido para redefinir a senha da sua conta na ProSaúde. "
                . "Acesse o link abaixo para criar uma nova senha (válido por "
                . self::VALIDADE_MINUTOS . " minutos):\n\n{$link}\n\n"
                . "Se você não fez esse pedido, ignore este e-mail.";

            try {
                Mail::raw($corpo, function ($mensagem) use ($email) {
                    $mensagem->to($email)->subject('ProSaúde - Redefinição de senha');
                });
            } catch (Throwable $e) {
                // Não revela ao visitante se o e-mail existe ou se o envio falhou
                report($e);
            }
        }

        // Mesma resposta para e-mails cadastrados e não cadastrados (evita enumeração de contas)
        return back()->with('status', 'Se o e-mail estiver cadastrado, enviaremos as instruções para criar uma nova senha.');
    }

    public function formulario(Request $request, string $token): View
    {
        return view('redefinirSenha', [
            'token' => $token,
            'email' => $request->query('email'),
            'tipo'  => $request->query('tipo'),
        ]);
    }

    public function redefinir(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'token'           => 'required|string',
            'email'           => 'required|email',
            'tipo'            => 'required|in:paciente,medico',
            'senha'           => 'required|string|min:8',
            'confirmar_senha' => 'required|same:senha',
        ]);

        $voltar = fn () => route('senha.redefinir', [
            'token' => $dados['token'],
            'email' => $dados['email'],
            'tipo'  => $dados['tipo'],
        ]);

        $registro = DB::table('password_reset_tokens')
            ->where('email', $dados['email'])
            ->where('tipo', $dados['tipo'])
            ->first();

        $linkValido = $registro
            && Hash::check($dados['token'], $registro->token)
            && Carbon::parse($registro->created_at)->gt(now()->subMinutes(self::VALIDADE_MINUTOS));

        $model   = self::MODELS[$dados['tipo']];
        $usuario = $linkValido ? $model::where('email', $dados['email'])->first() : null;

        if (!$usuario) {
            return redirect($voltar())->withErrors([
                'senha' => 'Link inválido ou expirado. Solicite uma nova recuperação de senha.',
            ]);
        }

        $usuario->update(['senha_hash' => Hash::make($dados['senha'])]);

        // Encerra as sessões abertas e invalida o link usado
        $usuario->tokens()->delete();
        DB::table('password_reset_tokens')
            ->where('email', $dados['email'])
            ->where('tipo', $dados['tipo'])
            ->delete();

        return redirect($voltar())->with('status', 'Senha redefinida com sucesso! Você já pode entrar com a nova senha.');
    }
}

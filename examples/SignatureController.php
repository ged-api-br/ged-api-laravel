<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Ged\ApiLaravel\Facades\GedApi;

/**
 * Exemplo de Controller Laravel usando GED API
 */
class SignatureController extends Controller
{
    /**
     * Inicia processo de assinatura
     */
    public function start(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
            'certificate' => 'required|file', // PFX
            'password' => 'required|string',
        ]);

        try {
            // Carrega PDF
            $pdf = $request->file('file');
            $pdfBase64 = base64_encode($pdf->get());

            // Inicia assinatura
            $start = GedApi::startSignature(
                $pdfBase64,
                config('ged-api.default_policy_oid')
            );

            // Carrega certificado PFX
            $pfxData = $request->file('certificate')->get();
            $password = $request->input('password');

            // Extrai chave privada e certificado
            openssl_pkcs12_read($pfxData, $certs, $password);

            // Assina o hash
            $signedAttrsDer = base64_decode($start['signedAttrsDerBase64']);
            openssl_sign($signedAttrsDer, $signature, $certs['pkey'], OPENSSL_ALGO_SHA256);

            // Finaliza assinatura
            $complete = GedApi::completeSignature(
                $start['pdfId'],
                base64_encode($signature),
                base64_encode($certs['cert'])
            );

            // Salva PDF assinado
            $signedPdf = base64_decode($complete['signedPdfBase64']);
            $filename = 'signed_' . time() . '.pdf';
            Storage::put("signatures/{$filename}", $signedPdf);

            return response()->json([
                'success' => true,
                'message' => 'PDF assinado com sucesso',
                'file' => $filename,
                'download_url' => route('signature.download', $filename),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Download do PDF assinado
     */
    public function download(string $filename)
    {
        $path = "signatures/{$filename}";

        if (!Storage::exists($path)) {
            abort(404);
        }

        return Storage::download($path);
    }

    /**
     * Verifica assinatura de um PDF
     */
    public function verify(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf',
        ]);

        try {
            $pdfBase64 = base64_encode($request->file('file')->get());
            
            $result = GedApi::verifySignature($pdfBase64);

            return response()->json([
                'success' => true,
                'valid' => $result['valid'],
                'signatures' => $result['signatures'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Novo fluxo PAdES (prepare → cms-params → inject → finalize)
     */
    public function startPades(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
            'certificate' => 'required|file', // PFX
            'password' => 'required|string',
        ]);

        try {
            // 1) Prepare (pode ser visível=false por padrão)
            $prepare = GedApi::padesPrepareFromFile($request->file('file')->getRealPath(), false);
            $documentId = $prepare['document_id'];

            // 2) Cms Params (dados para assinar localmente)
            $params = GedApi::padesCmsParams($documentId);

            // 3) Carrega certificado PFX e EXTRAI CADEIA COMPLETA ✅
            $pfxData = $request->file('certificate')->get();
            $password = $request->input('password');
            
            // ✅ IMPORTANTE: Usar CertificateHelper para extrair cadeia completa
            // Isso resolve o problema de "certificado inválido" no Adobe Reader
            $certHelper = new \Ged\ApiLaravel\Support\CertificateHelper();
            $certData = $certHelper->loadPfxFromContent($pfxData, $password);
            
            // Converter cadeia DER para base64
            $chainBase64 = array_map('base64_encode', $certData['chain']);

            // 4) Assina localmente os dados (A1)
            $toBeSignedDer = hex2bin($params['to_be_signed_der_hex']);
            openssl_sign($toBeSignedDer, $cmsDer, $certData['privateKey'], OPENSSL_ALGO_SHA256);
            $cmsDerHex = bin2hex($cmsDer);

            // 5) Inject COM CADEIA COMPLETA ✅
            $certBase64 = base64_encode($certData['certificate']);
            $inject = GedApi::padesInjectPkcs1(
                $documentId, 
                $params['field_name'], 
                $cmsDerHex,
                $certBase64,
                $chainBase64  // ← CADEIA COMPLETA (intermediários + raiz)
            );

            // 6) Finalize
            $final = GedApi::padesFinalize($documentId);
            $filename = 'signed_pades_' . time() . '.pdf';
            Storage::put("signatures/{$filename}", base64_decode($final['pdf_base64']));

            return response()->json([
                'success' => true,
                'message' => 'PDF assinado com PAdES e cadeia completa (Adobe Reader compatível)',
                'file' => $filename,
                'document_id' => $documentId,
                'chain_certificates' => count($chainBase64),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}


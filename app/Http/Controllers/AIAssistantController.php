<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Client;
use Exception;

class AIAssistantController extends Controller
{
    private $openaiClient;
    private $anthropicClient;

    public function __construct()
    {
        $this->openaiClient = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json'
            ]
        ]);

        $this->anthropicClient = new Client([
            'base_uri' => 'https://api.anthropic.com/v1/',
            'headers' => [
                'x-api-key' => config('services.anthropic.api_key'),
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ]
        ]);
    }

    public function correctWithChatGPT(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:2000'
        ]);

        try {
            $response = $this->openaiClient->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-4o-mini', // Modèle plus rapide et moins cher
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un correcteur professionnel francophone. Corrige uniquement les fautes d\'orthographe, de grammaire et de syntaxe du texte suivant, sans changer le style, le ton ou le fond du message. Si le texte est déjà correct, renvoie-le tel quel. Garde la même longueur et structure.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $request->content
                        ]
                    ],
                    'max_tokens' => 1500,
                    'temperature' => 0.1,
                    'presence_penalty' => 0,
                    'frequency_penalty' => 0
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['choices'][0]['message']['content'])) {
                return response()->json([
                    'success' => true,
                    'corrected_content' => trim($data['choices'][0]['message']['content'])
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Réponse invalide de l\'API'
                ], 500);
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur de communication avec l\'API : ' . $e->getMessage()
            ], 503);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la correction : ' . $e->getMessage()
            ], 500);
        }
    }

    public function improveWithChatGPT(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        try {
            $response = $this->openaiClient->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-4',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un rédacteur expert. Améliore le texte suivant en gardant l\'idée principale mais en rendant le style plus fluide, plus engageant et mieux structuré. Conserve le ton général de l\'auteur.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $request->content
                        ]
                    ],
                    'max_tokens' => 2000,
                    'temperature' => 0.3
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json([
                'success' => true,
                'improved_content' => $data['choices'][0]['message']['content']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'amélioration : ' . $e->getMessage()
            ], 500);
        }
    }

    public function improveWithClaude(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        try {
            $response = $this->anthropicClient->post('messages', [
                'json' => [
                    'model' => 'claude-3-sonnet-20240229',
                    'max_tokens' => 2000,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => "Tu es un rédacteur expert. Améliore le texte suivant en gardant l'idée principale mais en rendant le style plus fluide, plus engageant et mieux structuré. Conserve le ton général de l'auteur.\n\nTexte à améliorer :\n" . $request->content
                        ]
                    ]
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json([
                'success' => true,
                'improved_content' => $data['content'][0]['text']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'amélioration : ' . $e->getMessage()
            ], 500);
        }
    }
}

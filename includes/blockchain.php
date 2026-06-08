<?php
/**
 * IUC Voting System - Blockchain Integration
 * Smart contract and blockchain operations
 */

class BlockchainConnector {
    private $nodeUrl;
    private $contractAddress;
    private $contractAbi;
    
    public function __construct() {
        $this->nodeUrl = BLOCKCHAIN_NODE_URL;
        $this->contractAddress = SMART_CONTRACT_ADDRESS;
        $this->contractAbi = $this->getContractAbi();
    }
    
    /**
     * Get smart contract ABI
     */
    private function getContractAbi() {
        return [
            [
                "type" => "function",
                "name" => "createElection",
                "inputs" => [
                    ["name" => "electionId", "type" => "string"],
                    ["name" => "title", "type" => "string"],
                    ["name" => "candidates", "type" => "string[]"]
                ],
                "outputs" => []
            ],
            [
                "type" => "function",
                "name" => "castVote",
                "inputs" => [
                    ["name" => "electionId", "type" => "string"],
                    ["name" => "candidateId", "type" => "uint256"],
                    ["name" => "voterId", "type" => "string"]
                ],
                "outputs" => []
            ],
            [
                "type" => "function",
                "name" => "getElectionResults",
                "inputs" => [
                    ["name" => "electionId", "type" => "string"]
                ],
                "outputs" => [
                    ["name" => "candidates", "type" => "uint256[]"]
                ]
            ],
            [
                "type" => "function",
                "name" => "hasVoted",
                "inputs" => [
                    ["name" => "electionId", "type" => "string"],
                    ["name" => "voterId", "type" => "string"]
                ],
                "outputs" => [
                    ["name" => "voted", "type" => "bool"]
                ]
            ]
        ];
    }
    
    /**
     * Create a new election on blockchain
     */
    public function createElection($electionId, $title, $candidates) {
        try {
            $data = [
                'to' => $this->contractAddress,
                'data' => $this->encodeFunctionCall('createElection', [$electionId, $title, $candidates])
            ];
            
            $result = $this->sendTransaction($data);
            return [
                'success' => true,
                'transactionHash' => $result['hash'],
                'blockNumber' => $result['blockNumber']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cast a vote on blockchain
     */
    public function castVote($electionId, $candidateId, $voterId) {
        try {
            $data = [
                'to' => $this->contractAddress,
                'data' => $this->encodeFunctionCall('castVote', [$electionId, $candidateId, $voterId])
            ];
            
            $result = $this->sendTransaction($data);
            return [
                'success' => true,
                'transactionHash' => $result['hash'],
                'blockNumber' => $result['blockNumber']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get election results from blockchain
     */
    public function getElectionResults($electionId) {
        try {
            $data = [
                'to' => $this->contractAddress,
                'data' => $this->encodeFunctionCall('getElectionResults', [$electionId])
            ];
            
            $result = $this->callContract($data);
            return [
                'success' => true,
                'results' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if voter has already voted
     */
    public function hasVoted($electionId, $voterId) {
        try {
            $data = [
                'to' => $this->contractAddress,
                'data' => $this->encodeFunctionCall('hasVoted', [$electionId, $voterId])
            ];
            
            $result = $this->callContract($data);
            return [
                'success' => true,
                'hasVoted' => $result[0]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send transaction to blockchain
     */
    private function sendTransaction($data) {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'eth_sendTransaction',
            'params' => [
                [
                    'to' => $data['to'],
                    'data' => $data['data'],
                    'gas' => '0x' . dechex(GAS_LIMIT),
                    'gasPrice' => '0x' . dechex(20000000000) // 20 Gwei
                ]
            ],
            'id' => 1
        ];
        
        $response = $this->makeRequest($payload);
        return $response['result'];
    }
    
    /**
     * Call contract method (read-only)
     */
    private function callContract($data) {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'eth_call',
            'params' => [
                [
                    'to' => $data['to'],
                    'data' => $data['data']
                ],
                'latest'
            ],
            'id' => 1
        ];
        
        $response = $this->makeRequest($payload);
        return $this->decodeResult($response['result']);
    }
    
    /**
     * Make HTTP request to blockchain node
     */
    private function makeRequest($payload) {
        $ch = curl_init($this->nodeUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Blockchain node error: HTTP $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Encode function call
     */
    private function encodeFunctionCall($functionName, $params) {
        // Simplified encoding - in production, use proper ABI encoding
        $signature = keccak256($functionName);
        return '0x' . substr($signature, 0, 8) . '0000000000000000000000000000000000000000000000000000000000000000';
    }
    
    /**
     * Decode result from blockchain
     */
    private function decodeResult($hexString) {
        // Simplified decoding - in production, use proper ABI decoding
        return [intval($hexString, 16)];
    }
}

/**
 * Keccak256 hash function
 */
function keccak256($data) {
    return hash('sha3-256', $data);
}
?>

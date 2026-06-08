#!/usr/bin/env python3
"""
IUC Voting System - Blockchain Node
Python-based blockchain implementation for secure voting
"""

import hashlib
import json
import datetime
import uuid
from flask import Flask, request, jsonify
from flask_cors import CORS
import threading
import time
from typing import List, Dict, Any

class Block:
    """Represents a single block in the blockchain"""
    
    def __init__(self, index: int, transactions: List[Dict], previous_hash: str, proof: int = 0):
        self.index = index
        self.timestamp = str(datetime.datetime.now())
        self.transactions = transactions
        self.previous_hash = previous_hash
        self.proof = proof
        self.hash = self.calculate_hash()
    
    def calculate_hash(self) -> str:
        """Calculate the hash of the block"""
        block_string = json.dumps({
            "index": self.index,
            "timestamp": self.timestamp,
            "transactions": self.transactions,
            "previous_hash": self.previous_hash,
            "proof": self.proof
        }, sort_keys=True)
        return hashlib.sha256(block_string.encode()).hexdigest()
    
    def to_dict(self) -> Dict:
        """Convert block to dictionary"""
        return {
            "index": self.index,
            "timestamp": self.timestamp,
            "transactions": self.transactions,
            "previous_hash": self.previous_hash,
            "proof": self.proof,
            "hash": self.hash
        }

class Blockchain:
    """Main blockchain class for voting system"""
    
    def __init__(self):
        self.chain: List[Block] = []
        self.current_transactions: List[Dict] = []
        self.nodes = set()
        self.elections = {}  # Store election data
        self.votes = {}  # Store vote data
        
        # Create genesis block
        self.create_genesis_block()
        
        # Start mining thread
        self.mining_thread = threading.Thread(target=self.mine_pending_transactions, daemon=True)
        self.mining_thread.start()
    
    def create_genesis_block(self):
        """Create the first block in the chain"""
        genesis_block = Block(0, [], "0", 100)
        self.chain.append(genesis_block)
    
    def get_latest_block(self) -> Block:
        """Get the latest block in the chain"""
        return self.chain[-1]
    
    def add_transaction(self, transaction: Dict) -> int:
        """Add a new transaction to the pending transactions"""
        self.current_transactions.append(transaction)
        return self.get_latest_block().index + 1
    
    def proof_of_work(self, last_proof: int) -> int:
        """Simple Proof of Work algorithm"""
        proof = 0
        while not self.valid_proof(last_proof, proof):
            proof += 1
        return proof
    
    @staticmethod
    def valid_proof(last_proof: int, proof: int) -> bool:
        """Validate the proof"""
        guess = f'{last_proof}{proof}'.encode()
        guess_hash = hashlib.sha256(guess).hexdigest()
        return guess_hash[:4] == "0000"
    
    def mine_pending_transactions(self):
        """Mine pending transactions in a separate thread"""
        while True:
            if len(self.current_transactions) > 0:
                try:
                    last_block = self.get_latest_block()
                    last_proof = last_block.proof
                    proof = self.proof_of_work(last_proof)
                    
                    # Create new block
                    block = Block(
                        index=len(self.chain),
                        transactions=self.current_transactions.copy(),
                        previous_hash=last_block.hash,
                        proof=proof
                    )
                    
                    # Add block to chain
                    self.chain.append(block)
                    
                    # Clear pending transactions
                    self.current_transactions.clear()
                    
                    print(f"New block mined: {block.hash}")
                    
                except Exception as e:
                    print(f"Mining error: {e}")
            
            time.sleep(10)  # Mine every 10 seconds
    
    def create_election(self, election_id: str, title: str, candidates: List[str]) -> Dict:
        """Create a new election on the blockchain"""
        transaction = {
            "type": "create_election",
            "election_id": election_id,
            "title": title,
            "candidates": candidates,
            "timestamp": str(datetime.datetime.now()),
            "transaction_id": str(uuid.uuid4())
        }
        
        # Add to blockchain
        block_index = self.add_transaction(transaction)
        
        # Store election data
        self.elections[election_id] = {
            "title": title,
            "candidates": candidates,
            "created_at": str(datetime.datetime.now()),
            "status": "active",
            "votes": {}
        }
        
        return {
            "success": True,
            "transaction_id": transaction["transaction_id"],
            "block_index": block_index,
            "election_id": election_id
        }
    
    def cast_vote(self, election_id: str, candidate_id: int, voter_id: str) -> Dict:
        """Cast a vote in an election"""
        # Check if election exists
        if election_id not in self.elections:
            return {
                "success": False,
                "error": "Election not found"
            }
        
        # Check if voter has already voted
        if election_id in self.votes and voter_id in self.votes[election_id]:
            return {
                "success": False,
                "error": "Already voted in this election"
            }
        
        # Check if candidate is valid
        if candidate_id >= len(self.elections[election_id]["candidates"]):
            return {
                "success": False,
                "error": "Invalid candidate"
            }
        
        transaction = {
            "type": "cast_vote",
            "election_id": election_id,
            "candidate_id": candidate_id,
            "voter_id": voter_id,
            "timestamp": str(datetime.datetime.now()),
            "transaction_id": str(uuid.uuid4())
        }
        
        # Add to blockchain
        block_index = self.add_transaction(transaction)
        
        # Store vote data
        if election_id not in self.votes:
            self.votes[election_id] = {}
        
        self.votes[election_id][voter_id] = {
            "candidate_id": candidate_id,
            "timestamp": str(datetime.datetime.now()),
            "transaction_id": transaction["transaction_id"]
        }
        
        return {
            "success": True,
            "transaction_id": transaction["transaction_id"],
            "block_index": block_index,
            "election_id": election_id,
            "candidate_id": candidate_id
        }
    
    def get_election_results(self, election_id: str) -> Dict:
        """Get results for an election"""
        if election_id not in self.elections:
            return {
                "success": False,
                "error": "Election not found"
            }
        
        election = self.elections[election_id]
        candidates = election["candidates"]
        vote_counts = [0] * len(candidates)
        
        # Count votes
        if election_id in self.votes:
            for vote_data in self.votes[election_id].values():
                candidate_id = vote_data["candidate_id"]
                if candidate_id < len(vote_counts):
                    vote_counts[candidate_id] += 1
        
        return {
            "success": True,
            "election_id": election_id,
            "title": election["title"],
            "candidates": candidates,
            "vote_counts": vote_counts,
            "total_votes": sum(vote_counts)
        }
    
    def has_voted(self, election_id: str, voter_id: str) -> bool:
        """Check if a voter has already voted in an election"""
        return election_id in self.votes and voter_id in self.votes[election_id]
    
    def get_chain(self) -> List[Dict]:
        """Get the entire blockchain"""
        return [block.to_dict() for block in self.chain]
    
    def is_chain_valid(self, chain: List[Block] = None) -> bool:
        """Validate the entire blockchain"""
        if chain is None:
            chain = self.chain
        
        for i in range(1, len(chain)):
            current_block = chain[i]
            previous_block = chain[i - 1]
            
            # Check if the previous hash is correct
            if current_block.previous_hash != previous_block.hash:
                return False
            
            # Check if the hash is correct
            if current_block.hash != current_block.calculate_hash():
                return False
            
            # Check if the proof of work is valid
            if not self.valid_proof(previous_block.proof, current_block.proof):
                return False
        
        return True

# Flask API for blockchain node
app = Flask(__name__)
CORS(app)

# Initialize blockchain
blockchain = Blockchain()

@app.route('/blockchain', methods=['GET'])
def get_blockchain():
    """Get the entire blockchain"""
    return jsonify({
        "success": True,
        "chain": blockchain.get_chain(),
        "length": len(blockchain.chain)
    })

@app.route('/blockchain/mine', methods=['POST'])
def mine_block():
    """Mine a new block (manual mining)"""
    if len(blockchain.current_transactions) == 0:
        return jsonify({
            "success": False,
            "error": "No transactions to mine"
        })
    
    last_block = blockchain.get_latest_block()
    last_proof = last_block.proof
    proof = blockchain.proof_of_work(last_proof)
    
    # Create new block
    block = Block(
        index=len(blockchain.chain),
        transactions=blockchain.current_transactions.copy(),
        previous_hash=last_block.hash,
        proof=proof
    )
    
    # Add block to chain
    blockchain.chain.append(block)
    
    # Clear pending transactions
    blockchain.current_transactions.clear()
    
    return jsonify({
        "success": True,
        "block": block.to_dict(),
        "message": "Block mined successfully"
    })

@app.route('/election/create', methods=['POST'])
def create_election():
    """Create a new election"""
    data = request.get_json()
    
    election_id = data.get('election_id')
    title = data.get('title')
    candidates = data.get('candidates', [])
    
    if not election_id or not title or not candidates:
        return jsonify({
            "success": False,
            "error": "Missing required fields"
        })
    
    result = blockchain.create_election(election_id, title, candidates)
    return jsonify(result)

@app.route('/election/vote', methods=['POST'])
def cast_vote():
    """Cast a vote in an election"""
    data = request.get_json()
    
    election_id = data.get('election_id')
    candidate_id = data.get('candidate_id')
    voter_id = data.get('voter_id')
    
    if not election_id or candidate_id is None or not voter_id:
        return jsonify({
            "success": False,
            "error": "Missing required fields"
        })
    
    result = blockchain.cast_vote(election_id, candidate_id, voter_id)
    return jsonify(result)

@app.route('/election/results/<election_id>', methods=['GET'])
def get_election_results(election_id):
    """Get results for an election"""
    result = blockchain.get_election_results(election_id)
    return jsonify(result)

@app.route('/election/has_voted', methods=['POST'])
def has_voted():
    """Check if a voter has already voted"""
    data = request.get_json()
    
    election_id = data.get('election_id')
    voter_id = data.get('voter_id')
    
    if not election_id or not voter_id:
        return jsonify({
            "success": False,
            "error": "Missing required fields"
        })
    
    has_voted = blockchain.has_voted(election_id, voter_id)
    return jsonify({
        "success": True,
        "has_voted": has_voted
    })

@app.route('/blockchain/validate', methods=['GET'])
def validate_blockchain():
    """Validate the blockchain"""
    is_valid = blockchain.is_chain_valid()
    return jsonify({
        "success": True,
        "is_valid": is_valid
    })

@app.route('/transactions/pending', methods=['GET'])
def get_pending_transactions():
    """Get pending transactions"""
    return jsonify({
        "success": True,
        "transactions": blockchain.current_transactions,
        "count": len(blockchain.current_transactions)
    })

@app.route('/stats', methods=['GET'])
def get_stats():
    """Get blockchain statistics"""
    return jsonify({
        "success": True,
        "stats": {
            "total_blocks": len(blockchain.chain),
            "pending_transactions": len(blockchain.current_transactions),
            "total_elections": len(blockchain.elections),
            "total_votes": sum(len(votes) for votes in blockchain.votes.values()),
            "is_valid": blockchain.is_chain_valid()
        }
    })

if __name__ == '__main__':
    print("Starting IUC Voting System Blockchain Node...")
    print("Blockchain node will be available at: http://localhost:5000")
    app.run(host='0.0.0.0', port=5000, debug=True)

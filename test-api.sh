#!/bin/bash

BASE_URL="http://localhost:8000/api"
EMAIL="test@example.com"
PASSWORD="password"

echo "1. Registering User..."
curl -s -X POST $BASE_URL/register \
  -H "Content-Type: application/json" \
  -d "{\"name\": \"Test User\", \"email\": \"$EMAIL\", \"password\": \"$PASSWORD\"}" | jq

echo -e "\n\n2. Logging in..."
TOKEN=$(curl -s -X POST $BASE_URL/login \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"$EMAIL\", \"password\": \"$PASSWORD\"}" | jq -r '.access_token')

echo "Token: $TOKEN"

echo -e "\n\n3. Getting Profile..."
curl -s -X GET $BASE_URL/me \
  -H "Authorization: Bearer $TOKEN" | jq

echo -e "\n\n4. Creating API Key..."
API_KEY=$(curl -s -X POST $BASE_URL/api-keys \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Shell Script Key"}' | jq -r '.key')

echo "API Key: $API_KEY"

echo -e "\n\n5. Accessing Service with API Key..."
curl -s -X GET $BASE_URL/service \
  -H "X-API-KEY: $API_KEY" | jq

echo -e "\n\nDone!"

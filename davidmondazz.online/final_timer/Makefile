# Timer Tracker System Makefile
# Makes installation and management easier across platforms

# Variables
PHP = php
COMPOSER = composer
NODE = node
NPM = npm
EXPO = expo
SERVER_IP ?= 127.0.0.1
WS_PORT ?= 8080

# Default target
.PHONY: help
help:
	@echo "Timer Tracker System - Build Targets"
	@echo "===================================="
	@echo ""
	@echo "Web Application:"
	@echo "  make install-web        - Install web app dependencies"
	@echo "  make start-server       - Start WebSocket server"
	@echo ""
	@echo "Android App:"
	@echo "  make install-app        - Install Android app dependencies"
	@echo "  make configure-app      - Configure WebSocket connection (SERVER_IP=x.x.x.x WS_PORT=8080)"
	@echo "  make start-app          - Start Expo development server"
	@echo "  make build-apk          - Build Android APK"
	@echo ""
	@echo "Combined:"
	@echo "  make install-all        - Install all dependencies"
	@echo "  make setup-all          - Complete setup (SERVER_IP=x.x.x.x)"
	@echo ""
	@echo "Example:"
	@echo "  make setup-all SERVER_IP=192.168.1.10"

# Web application targets
.PHONY: install-web
install-web:
	@echo "Installing web application dependencies..."
	$(COMPOSER) require cboden/ratchet

.PHONY: start-server
start-server:
	@echo "Starting WebSocket server on port $(WS_PORT)..."
	$(PHP) ws_server.php

# Android app targets
.PHONY: install-app
install-app:
	@echo "Installing Android app dependencies..."
	cd android_app && $(NPM) install

.PHONY: configure-app
configure-app:
	@echo "Configuring WebSocket connection to $(SERVER_IP):$(WS_PORT)..."
	cd android_app && $(NODE) app_builder.js setup $(SERVER_IP) $(WS_PORT)

.PHONY: start-app
start-app:
	@echo "Starting Expo development server..."
	cd android_app && $(EXPO) start

.PHONY: build-apk
build-apk:
	@echo "Building Android APK..."
	cd android_app && $(EXPO) build:android -t apk

# Combined targets
.PHONY: install-all
install-all: install-web install-app
	@echo "All dependencies installed successfully!"

.PHONY: setup-all
setup-all: install-all configure-app
	@echo "Setup completed successfully!"
	@echo "Next steps:"
	@echo "1. Start the WebSocket server: make start-server"
	@echo "2. Start the Expo dev server: make start-app" 
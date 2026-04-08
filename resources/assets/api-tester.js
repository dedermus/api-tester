/**
 * Advanced API Tester - Main JavaScript File
 */

class APITester {
    constructor() {
        this.config = {
            baseUrl: window.location.origin,
            apiUrl: window.location.origin + '/admin/api-tester',
            defaultHeaders: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        this.state = {
            currentRequest: null,
            collections: [],
            environments: {},
            history: [],
            settings: this.loadSettings()
        };

        this.init();
    }

    init() {
        this.bindEvents();
        this.loadCollections();
        this.loadEnvironments();
        this.loadHistory();
        this.setupCodeEditor();
    }

    bindEvents() {
        // Метод выбора
        document.querySelectorAll('.method-selector button').forEach(btn => {
            btn.addEventListener('click', (e) => this.selectMethod(e.target.dataset.method));
        });

        // Выбор роута
        document.querySelectorAll('.route-item').forEach(item => {
            item.addEventListener('click', (e) => this.selectRoute(item));
        });

        // Отправка запроса
        document.getElementById('send-request')?.addEventListener('click', () => this.sendRequest());

        // Сохранение коллекции
        document.getElementById('save-collection')?.addEventListener('click', () => this.saveCollection());

        // Экспорт/импорт
        document.getElementById('export-collection')?.addEventListener('click', () => this.exportCollection());
        document.getElementById('import-collection')?.addEventListener('click', () => this.importCollection());

        // Быстрые тесты
        document.querySelectorAll('[data-test]').forEach(btn => {
            btn.addEventListener('click', (e) => this.runQuickTest(e.target.dataset.test));
        });

        // Генерация кода
        document.getElementById('generate-code')?.addEventListener('click', () => this.generateCode());

        // Копирование ответа
        document.getElementById('copy-response')?.addEventListener('click', () => this.copyToClipboard('response-body-content'));

        // Сохранение ответа
        document.getElementById('save-response')?.addEventListener('click', () => this.saveResponse());

        // Очистка логов
        document.getElementById('clear-logs')?.addEventListener('click', () => this.clearLogs());

        // Экспорт логов
        document.getElementById('export-logs')?.addEventListener('click', () => this.exportLogs());
    }

    selectMethod(method) {
        // Обновляем активную кнопку
        document.querySelectorAll('.method-selector button').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.method === method);
        });

        // Обновляем скрытое поле
        document.getElementById('request-method').value = method;

        // Показываем/скрываем таб body в зависимости от метода
        const bodyTab = document.querySelector('a[href="#body-tab"]');
        const bodyPane = document.getElementById('body-tab');

        if (['GET', 'HEAD', 'DELETE'].includes(method)) {
            bodyTab.style.display = 'none';
            bodyPane.style.display = 'none';
        } else {
            bodyTab.style.display = 'block';
            bodyPane.style.display = 'block';
        }
    }

    selectRoute(routeElement) {
        // Обновляем активный элемент
        document.querySelectorAll('.route-item').forEach(item => {
            item.classList.remove('active');
        });
        routeElement.classList.add('active');

        // Получаем данные роута
        const routeData = {
            uri: routeElement.dataset.uri,
            method: routeElement.dataset.method,
            parameters: JSON.parse(routeElement.dataset.parameters || '[]'),
            action: routeElement.dataset.action
        };

        // Обновляем URL и метод
        document.getElementById('request-url').value =
            this.state.settings.currentEnvironment + '/' + routeData.uri;

        this.selectMethod(routeData.method);

        // Загружаем параметры
        this.loadRouteParameters(routeData.parameters);

        // Генерируем cURL и код
        this.generateCurlCommand();
        this.generateCode();
    }

    loadRouteParameters(parameters) {
        const tbody = document.getElementById('query-params-body');
        tbody.innerHTML = '';

        parameters.forEach(param => {
            this.addParameterRow(param);
        });
    }

    addParameterRow(param = {}) {
        const template = document.getElementById('param-row-template');
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('tr');

        if (param.name) {
            row.querySelector('.param-key').value = param.name;
        }

        if (param.defaultValue) {
            row.querySelector('.param-value').value = param.defaultValue;
        }

        if (param.type) {
            row.querySelector('.param-type').value = param.type;
        }

        if (param.required === 'true') {
            row.querySelector('.param-required').checked = true;
        }

        // Обработчик удаления
        row.querySelector('.param-remove').addEventListener('click', () => {
            row.remove();
            this.generateCurlCommand();
        });

        document.getElementById('query-params-body').appendChild(row);
    }

    async sendRequest() {
        const url = document.getElementById('request-url').value;
        const method = document.getElementById('request-method').value;

        if (!url) {
            this.showToast('Please enter a URL', 'error');
            return;
        }

        // Подготавливаем данные запроса
        const requestData = this.prepareRequestData();

        // Показываем индикатор загрузки
        const sendBtn = document.getElementById('send-request');
        const originalText = sendBtn.innerHTML;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        sendBtn.disabled = true;

        const startTime = Date.now();

        try {
            const response = await fetch(this.config.apiUrl + '/handle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    ...this.config.defaultHeaders
                },
                body: new URLSearchParams(requestData)
            });

            const data = await response.json();
            const duration = Date.now() - startTime;

            // Обрабатываем ответ
            this.handleResponse(data, duration);

            // Сохраняем в историю
            this.saveToHistory({
                method,
                url,
                status: data.status_code,
                duration,
                timestamp: new Date().toISOString()
            });

        } catch (error) {
            console.error('Request failed:', error);
            this.showToast('Request failed: ' + error.message, 'error');
        } finally {
            // Восстанавливаем кнопку
            sendBtn.innerHTML = originalText;
            sendBtn.disabled = false;
        }
    }

    prepareRequestData() {
        const data = {
            _token: document.querySelector('meta[name="csrf-token"]').content,
            method: document.getElementById('request-method').value,
            uri: document.getElementById('request-url').value.replace(this.state.settings.currentEnvironment + '/', ''),
            auth_type: document.getElementById('auth-type').value === 'none' ? 'no_auth' : document.getElementById('auth-type').value
        };

        // Добавляем параметры
        document.querySelectorAll('#query-params-body tr').forEach((row, index) => {
            const key = row.querySelector('.param-key').value;
            const value = row.querySelector('.param-value').value;
            if (key) {
                data[`key[${index}]`] = key;
                data[`val[${index}]`] = value;
            }
        });

        // Добавляем данные аутентификации
        const authType = document.getElementById('auth-type').value;
        if (authType === 'basic') {
            data.basic_auth_username = document.getElementById('basic-username').value;
            data.basic_auth_password = document.getElementById('basic-password').value;
        } else if (authType === 'bearer') {
            data.bearer_token_token = document.getElementById('bearer-token').value;
        }

        return data;
    }

    handleResponse(data, duration) {
        // Обновляем заголовок ответа
        document.getElementById('response-header').style.display = 'flex';

        // Статус
        const status = data.status_code || 500;
        const statusBadge = document.getElementById('response-status');
        statusBadge.textContent = status + ' ' + (data.status_text || '');
        statusBadge.className = 'status-badge ' + this.getStatusClass(status);

        // Время и размер
        document.getElementById('response-time').textContent = '⏱ ' + duration + 'ms';

        const size = new Blob([data.content || '']).size;
        document.getElementById('response-size').textContent = '📦 ' + this.formatBytes(size);

        // Тело ответа
        let content = data.content;
        try {
            const json = JSON.parse(data.content);
            content = JSON.stringify(json, null, 2);
        } catch (e) {
            // Не JSON
        }

        document.getElementById('response-body-content').textContent = content;

        // Заголовки
        let headers = data.headers;
        try {
            const json = JSON.parse(data.headers);
            headers = JSON.stringify(json, null, 2);
        } catch (e) {
            // Оставляем как есть
        }

        document.getElementById('response-headers-content').textContent = headers;

        // Показываем соответствующую иконку
        const successIcon = document.getElementById('success-icon');
        const errorIcon = document.getElementById('error-icon');

        if (status >= 200 && status < 300) {
            successIcon.style.display = 'inline';
            errorIcon.style.display = 'none';
            this.showToast('Request successful', 'success');
        } else {
            successIcon.style.display = 'none';
            errorIcon.style.display = 'inline';
            this.showToast(`Request failed: ${status}`, 'error');
        }

        // Подсветка синтаксиса
        if (window.Prism) {
            Prism.highlightAll();
        }
    }

    getStatusClass(status) {
        if (status >= 200 && status < 300) return 'bg-success';
        if (status >= 300 && status < 400) return 'bg-info';
        if (status >= 400 && status < 500) return 'bg-warning';
        return 'bg-danger';
    }

    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    generateCurlCommand() {
        const method = document.getElementById('request-method').value;
        const url = document.getElementById('request-url').value;

        let curl = `curl -X ${method} \\\n  "${url}"`;

        // Заголовки
        curl += ` \\\n  -H "Content-Type: application/json"`;
        curl += ` \\\n  -H "Accept: application/json"`;

        // Параметры
        const params = [];
        document.querySelectorAll('#query-params-body tr').forEach(row => {
            const key = row.querySelector('.param-key').value;
            const value = row.querySelector('.param-value').value;
            if (key && value) {
                params.push(`${key}=${encodeURIComponent(value)}`);
            }
        });

        if (params.length > 0) {
            curl += ` \\\n  -d "${params.join('&')}"`;
        }

        document.getElementById('curl-command').textContent = curl;
    }

    generateCode() {
        const language = document.getElementById('code-language').value;
        const method = document.getElementById('request-method').value;
        const url = document.getElementById('request-url').value;

        let code = '';

        switch(language) {
            case 'javascript':
                code = this.generateJSCode(method, url);
                break;
            case 'axios':
                code = this.generateAxiosCode(method, url);
                break;
            case 'php':
                code = this.generatePHPCode(method, url);
                break;
            case 'python':
                code = this.generatePythonCode(method, url);
                break;
            default:
                code = '# Select a language';
        }

        document.getElementById('generated-code').textContent = code;
    }

    generateJSCode(method, url) {
        return `fetch('${url}', {
  method: '${method}',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));`;
    }

    generateAxiosCode(method, url) {
        return `axios.${method.toLowerCase()}('${url}', {
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})
.then(response => console.log(response.data))
.catch(error => console.error('Error:', error));`;
    }

    generatePHPCode(method, url) {
        return `<?php

$client = new \\GuzzleHttp\\Client();

try {
    $response = $client->request('${method}', '${url}', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]
    ]);

    $data = json_decode($response->getBody(), true);
    print_r($data);

} catch (\\Exception $e) {
    echo 'Error: ' . $e->getMessage();
}`;
    }

    generatePythonCode(method, url) {
        return `import requests
import json

try:
    response = requests.${method.toLowerCase()}('${url}',
        headers={
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    )

    response.raise_for_status()
    data = response.json()
    print(json.dumps(data, indent=2))

except requests.exceptions.RequestException as e:
    print(f'Error: {e}')`;
    }

    runQuickTest(testType) {
        switch(testType) {
            case 'valid':
                this.setValidTestData();
                break;
            case 'no-auth':
                this.setNoAuthTest();
                break;
            case 'invalid':
                this.setInvalidTestData();
                break;
            case 'load':
                this.setLoadTestData();
                break;
        }
    }

    setValidTestData() {
        document.getElementById('request-body').value = JSON.stringify({
            test: 'valid',
            timestamp: new Date().toISOString(),
            data: {
                id: 1,
                name: 'Test Item'
            }
        }, null, 2);

        this.showToast('Valid test data loaded', 'info');
    }

    setNoAuthTest() {
        document.getElementById('auth-type').value = 'none';
        this.showAuthFields('none');
        this.showToast('Authentication removed', 'info');
    }

    setInvalidTestData() {
        document.getElementById('request-body').value = 'invalid-json';
        this.showToast('Invalid test data loaded', 'warning');
    }

    setLoadTestData() {
        const largeArray = Array.from({length: 50}, (_, i) => ({
            id: i + 1,
            name: `Item ${i + 1}`,
            description: 'A sample item for load testing',
            timestamp: new Date().toISOString()
        }));

        document.getElementById('request-body').value = JSON.stringify({
            items: largeArray,
            metadata: {
                count: largeArray.length,
                generated: new Date().toISOString()
            }
        }, null, 2);

        this.showToast('Load test data generated', 'info');
    }

    showAuthFields(type) {
        document.querySelectorAll('.auth-field').forEach(field => {
            field.style.display = 'none';
        });

        const field = document.querySelector(`.auth-field[data-type="${type}"]`);
        if (field) {
            field.style.display = 'block';
        }
    }

    async copyToClipboard(elementId) {
        const text = document.getElementById(elementId).textContent;

        try {
            await navigator.clipboard.writeText(text);
            this.showToast('Copied to clipboard', 'success');
        } catch (err) {
            console.error('Failed to copy: ', err);
            this.showToast('Failed to copy', 'error');
        }
    }

    saveResponse() {
        const content = document.getElementById('response-body-content').textContent;
        const blob = new Blob([content], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');

        a.href = url;
        a.download = `api-response-${new Date().getTime()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        this.showToast('Response saved', 'success');
    }

    async clearLogs() {
        if (!confirm('Are you sure you want to clear all logs?')) return;

        try {
            const response = await fetch(this.config.apiUrl + '/clear-logs', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    ...this.config.defaultHeaders
                }
            });

            const data = await response.json();

            if (data.status === 'ok') {
                document.getElementById('logs-list').innerHTML = '';
                this.updateLogsCount(0);
                this.showToast('Logs cleared', 'success');
            } else {
                this.showToast('Failed to clear logs', 'error');
            }
        } catch (error) {
            console.error('Error clearing logs:', error);
            this.showToast('Error clearing logs', 'error');
        }
    }

    exportLogs() {
        window.open(this.config.apiUrl + '/download-logs', '_blank');
    }

    updateLogsCount(count) {
        document.getElementById('logs-count').textContent = count || 0;
    }

    showToast(message, type = 'info') {
        // Используем toastr из OpenAdmin
        if (window.admin && admin.toastr) {
            admin.toastr[type](message);
        } else {
            alert(message);
        }
    }

    loadSettings() {
        const defaultSettings = {
            currentEnvironment: window.location.origin,
            theme: 'light',
            autoFormat: true,
            saveHistory: true
        };

        try {
            return JSON.parse(localStorage.getItem('apiTesterSettings')) || defaultSettings;
        } catch (e) {
            return defaultSettings;
        }
    }

    saveSettings() {
        localStorage.setItem('apiTesterSettings', JSON.stringify(this.state.settings));
    }

    async loadCollections() {
        try {
            const collections = JSON.parse(localStorage.getItem('apiTesterCollections')) || [];
            this.state.collections = collections;
            this.renderCollections();
        } catch (e) {
            console.error('Error loading collections:', e);
        }
    }

    renderCollections() {
        const container = document.getElementById('collections-list');
        if (!container) return;

        container.innerHTML = '';

        this.state.collections.forEach(collection => {
            const div = document.createElement('div');
            div.className = 'collection-item';
            div.innerHTML = `
                <i class="fas fa-folder me-2"></i>
                <strong>${collection.name}</strong>
                <small class="d-block text-muted">${collection.endpoints?.length || 0} endpoints</small>
            `;

            div.addEventListener('click', () => this.loadCollection(collection));
            container.appendChild(div);
        });
    }

    async saveCollection() {
        const name = prompt('Enter collection name:');
        if (!name) return;

        const collection = {
            id: Date.now(),
            name: name,
            endpoints: [],
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        };

        this.state.collections.push(collection);
        localStorage.setItem('apiTesterCollections', JSON.stringify(this.state.collections));

        this.renderCollections();
        this.showToast('Collection saved', 'success');
    }

    loadCollection(collection) {
        // Здесь можно реализовать загрузку коллекции
        this.showToast(`Loaded collection: ${collection.name}`, 'info');
    }

    exportCollection() {
        // Реализация экспорта коллекции
    }

    importCollection() {
        // Реализация импорта коллекции
    }

    loadEnvironments() {
        // Загрузка окружений
    }

    loadHistory() {
        // Загрузка истории
    }

    saveToHistory(request) {
        // Сохранение в историю
    }

    setupCodeEditor() {
        // Настройка редактора кода (можно использовать CodeMirror или Monaco)
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    window.apiTester = new APITester();
});

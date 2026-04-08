<style>
    .param-add,.param {
        margin-bottom: 10px;
    }

    .param-add .form-group,.param .form-group {
        margin: 0;
    }

    .status-label {
        margin: 10px;
    }

    .response-tabs pre {
        border-radius: 0px;
    }

    .param-remove {
        margin-left: 5px !important;
    }

    .param-desc {
        display: block;
        margin-top: 5px;
        margin-bottom: 10px;
        color: #737373;
    }

    .nav-stacked>li {
        border-bottom: 1px solid #f4f4f4;
        margin: 0 !important;
    }

    .nav>li>a {
        padding: 10px 10px;
    }
    .card-header h2{
        font-size:1.5rem;
        margin-bottom:0;
    }
    .hidden {
        display: none;
    }
</style>

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header"><h2 class="">Routes</h2></div>
            <div class="card-body">
                <form action="#" method="post">
                    <div class="input-group">
                        <input type="text" name="message" placeholder="Type Url ..." class="form-control filter-routes">
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-primary btn-flat"><i class="icon-search"></i></button>
                        </span>
                    </div>
                </form>
                <div class="d-flex justify-content-between mt-2 mb-2 align-items-center">
                    <div>
                        <span class="badge bg-secondary logs-count">Загрузка...</span>
                    </div>
                    <div>
                        <a href="{{ route('api-tester-logs') }}" class="btn btn-sm btn-info view-logs-btn" target="_blank" title="Просмотр в формате JSON">
                            <i class="icon-eye"></i>
                        </a>
                        <a href="{{ route('api-tester-download-logs') }}" class="btn btn-sm btn-success download-logs-btn" title="Скачать JSON файл">
                            <i class="icon-download"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-danger clear-logs-btn" title="Очистить все логи">
                            <i class="icon-trash"></i>
                        </button>
                    </div>
                </div>

                <ul class="nav nav-pills nav-stacked routes" style="margin-top: 5px;">
                    @foreach($routes as $route)
                        @php ($color = OpenAdminCore\Admin\ApiTester\ApiTester::$methodColors[$route['method']])
                        <li class="route-item w-100"
                            data-uri="{{ $route['uri'] }}"
                            data-method="{{ $route['method'] }}"
                            data-method_color="{{ $color }}"
                            data-parameters='{{ $route['parameters'] }}'>

                            <a href="javascript:void(0)" class="route-link d-flex justify-content-between">
                                <b>{{ $route['uri'] }}</b>
                                <div class="">
                                    <span class="badge bg-{{ $color }}">{{ $route['method'] }}</span>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card">
            <form id="api-tester-form" method="post" class="form-horizontal api-tester-form" enctype="multipart/form-data" autocomplete="off" action="{{ route('api-tester-handle') }}">
                <div class="card-header"><h2 class="">Request</h2></div>
                <div class="card-body">
                    <div class="row form-group">
                        <label for="uri" class="col-sm-2 form-label">Request</label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <a class="input-group-text method">GET</a>
                                </div>

                                    <input type="text" name="uri" id="uri" class="form-control uri">

                                <input type="hidden" name="method" class="form-control method" value="GET">
                                {{ csrf_field() }}
                            </div>
                        </div>
                    </div>

                    <div class="row form-group">
                        <label for="auth_type" class="col-sm-2 form-label">Auth Type</label>
                        <div class="col-sm-10">
                            <label for="auth_type">
                                <select name="auth_type"  id="auth_type" class="form-control">
                                    @foreach($auth_type as $item)
                                        <option value="{{ $item['value'] }}" {{ $item['select'] ? 'selected' : '' }}>
                                            {{ $item['title'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="hidden" id="no_auth">
                        <div class="row form-group">
                            <label for="inputUser" class="col-sm-2 form-label">Login as</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="inputUser" name="user" placeholder="Enter a user id or token to login with specific user.">
                            </div>
                        </div>
                    </div>

                    <div class="hidden" id="basic_auth">
                        <div class="row form-group">
                            <label for="basic_auth_username" class="col-sm-3 form-label">Username</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="basic_auth_username" name="basic_auth_username" placeholder="Enter login.">
                            </div>
                        </div>
                        <div class="row form-group">
                            <label for="basic_auth_password" class="col-sm-3 form-label">Password</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="basic_auth_password" name="basic_auth_password" placeholder="Enter password.">
                            </div>
                        </div>
                    </div>

                    <div class="hidden" id="bearer_token">
                        <div class="row form-group">
                            <label for="bearer_token_token" class="col-sm-3 form-label">Token</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="bearer_token_token" name="bearer_token_token" placeholder="Enter token.">
                            </div>
                        </div>
                    </div>

                    <div class="row form-group">
                        <label class="col-sm-2 form-label" for="add_params">Parameters</label>
                        <div class="col-sm-10">
                            <div class="params"></div>
                            <a class="btn btn-primary param-add" id="add_params">Add param</a>
                        </div>
                    </div>
                </div>
                <div class="card-footer" style="margin-bottom: 0px;">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary">Send</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card mt-5">
            <div class="nav-tabs-custom response-tabs d-none">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link active" data-bs-target="#content" aria-controls="content" data-bs-toggle="tab">Content</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-target="#headers" aria-controls="headers" data-bs-toggle="tab">Headers</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-target="#cookies" aria-controls="cookies" data-bs-toggle="tab">Cookies</a></li>
                    <li class="nav-item"><a class="nav-link response-status"></a></li>
                </ul>
                <div class="tab-content card-body">
                    <div class="tab-pane active" id="content">
                        <pre><code class="language-json line-numbers"></code></pre>
                    </div>
                    <div class="tab-pane" id="headers">
                        <pre><code class="language-json line-numbers"></code></pre>
                    </div>
                    <div class="tab-pane" id="cookies">
                        <pre><code class="language-json line-numbers"></code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<template class="param-tpl">
    <div class="form-inline param d-flex">
        <div class="form-group me-2">
            <input type="text" name="key[__index__]" class="form-control param-key" style="width: 120px" placeholder="Key"/>
        </div>
        <div class="form-group">
            <div class="input-group">
                <input type="text" name="val[__index__]" class="form-control param-val" style="width: 280px" placeholder="value"/>
                <span class="input-group-btn">
                    <a type="button" class="btn btn-default btn-light change-val-type"><i class="icon-upload"></i></a>
                </span>
            </div>
        </div>
        <div class="form-group text-danger">
            <i class="btn btn-danger icon-times-circle param-remove"></i>
        </div>
        <br/>
        <div class="form-group param-desc d-none p-2">
            <i class="icon-info-circle"></i>&nbsp;
            <span class="text"></span>
            <b class="text-danger d-none param-required">*</b>
        </div>
    </div>
</template>

<script data-exec-on-popstate>
    (function () {
        // Определи функцию внутри этого блока
        function toggleFields(selectedValue) {
            document.getElementById('no_auth').classList.add('hidden');
            document.getElementById('basic_auth').classList.add('hidden');
            document.getElementById('bearer_token').classList.add('hidden');

            if (selectedValue === 'no_auth') {
                document.getElementById('no_auth').classList.remove('hidden');
            } else if (selectedValue === 'basic_auth') {
                document.getElementById('basic_auth').classList.remove('hidden');
            } else if (selectedValue === 'bearer_token') {
                document.getElementById('bearer_token').classList.remove('hidden');
            }
        }

        // При загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            var selectElement = document.querySelector('select[name="auth_type"]');
            if (selectElement) {
                toggleFields(selectElement.value); // начальное состояние

                // Добавь обработчик события
                selectElement.addEventListener('change', function() {
                    toggleFields(this.value);
                });
            }

            // Вызываем функцию обновления счетчика логов
            updateLogsCount();
        });

        var timer;
        var filterRoutes = document.querySelector('.filter-routes');
        if (filterRoutes) {
            filterRoutes.addEventListener('keyup', function (e) {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    var search = e.target.value;
                    var regex = search ? new RegExp(search, 'i') : null;
                    document.querySelectorAll('ul.routes li').forEach(el => {
                        if (!regex || regex.test(el.dataset.uri)) {
                            el.classList.remove('d-none');
                        } else {
                            el.classList.add('d-none');
                        }
                    });
                }, 300);
            });
        }

        document.querySelectorAll(".route-item").forEach(el => {
            el.addEventListener("click", function (e) {
                var li = e.currentTarget;
                var a_method = document.querySelector('a.method');
                a_method.textContent = li.dataset.method;
                a_method.className = 'input-group-text method bg-' + li.dataset.method_color + ' text-white';

                document.querySelector('.uri').value = li.dataset.uri;
                document.querySelector('input.method').value = li.dataset.method;

                document.querySelectorAll('.param').forEach(el => el.remove());

                var params;
                try {
                    params = JSON.parse(li.dataset.parameters || '[]');
                } catch (ex) {
                    params = [];
                }
                appendParameters(params);

                document.querySelector('.response-tabs').classList.remove('d-none');
            });
        });

        function getParamCount() {
            return document.querySelectorAll('.param').length;
        }

        function appendParameters(params) {
            params.forEach(param => {
                var html = document.querySelector('template.param-tpl').innerHTML;
                html = html.replace(/__index__/g, getParamCount());
                var append = htmlToElement(html);

                append.querySelector('.param-key').value = param.name || '';
                append.querySelector('.param-val').value = param.defaultValue || '';

                if (param.description) {
                    append.querySelector('.param-desc').classList.remove('d-none');
                    append.querySelector('.param-desc .text').textContent = param.description;
                }

                if (param.required === 'true') {
                    append.querySelector('.param-desc .param-required').classList.remove('d-none');
                }

                if (param.type === 'file') {
                    append.querySelector('.param-val').setAttribute('type', 'file');
                    var icon = append.querySelector('.change-val-type i');
                    icon.classList.remove('icon-upload');
                    icon.classList.add('icon-pen');
                }

                document.querySelector('.params').append(append);
            });
        }

        document.addEventListener('click', function (event) {
            var target = event.target.closest(".change-val-type");
            if (target) {
                var input = target.closest('.input-group').querySelector('.param-val');
                var icon = target.querySelector('i');
                if (input.type === 'text') {
                    input.type = 'file';
                    icon.classList.remove('icon-upload');
                    icon.classList.add('icon-pen');
                } else {
                    input.type = 'text';
                    icon.classList.remove('icon-pen');
                    icon.classList.add('icon-upload');
                }
            }

            var target2 = event.target.closest(".param-remove");
            if (target2) {
                target2.closest('.param').remove();
            }
        });

        var paramAddBtn = document.querySelector('.param-add');
        if (paramAddBtn) {
            paramAddBtn.addEventListener('click', function () {
                var html = document.querySelector('template.param-tpl').innerHTML;
                html = html.replace(/__index__/g, getParamCount());
                var append = htmlToElement(html);
                document.querySelector('.params').append(append);
                append.querySelector('input:first-child').focus();
            });
        }

        function renderResponse(data) {
            document.querySelector('#content pre code').textContent = data.content || '';
            document.querySelector('#headers pre code').textContent = data.headers || '';
            document.querySelector('#cookies pre code').textContent = data.cookies || '';

            var lang = data.language || 'json';
            document.querySelectorAll('.response-tabs pre code').forEach(el => {
                el.className = 'language-' + lang + ' line-numbers';
            });

            Prism.highlightAll();

            var statusEl = document.querySelector('.response-status');
            statusEl.textContent = 'Status: ' + data.status_code + ' ' + data.status_text;
            statusEl.className = 'nav-link response-status ' + (data.status_code >= 400 ? 'text-danger' : 'text-success');
        }

        var apiTesterForm = document.getElementById('api-tester-form');
        if (apiTesterForm) {
            apiTesterForm.addEventListener('submit', function (event) {
                event.preventDefault();
                let form = this;

                admin.form.submit(form, function (result) {
                    if (result) {
                        if (result.data.status_code === 200) {
                            admin.toastr.success(result.data.message);
                        } else if (result.data.status === 'error') {
                            admin.toastr.info(result.data.message);
                        } else {
                            admin.toastr.error(result.data.message);
                        }
                        renderResponse(result.data);
                    } else {
                        let msg = result?.message || 'Unknown error';
                        admin.toastr.error(msg);
                        console.error('API Tester error:', result);
                    }
                });
            });
        }

        // Кнопка очистки логов
        var clearLogsBtn = document.querySelector('.clear-logs-btn');
        if (clearLogsBtn) {
            clearLogsBtn.addEventListener('click', function() {
                if (confirm('Вы уверены, что хотите очистить все логи? Это действие нельзя отменить.')) {
                    // Используем fetch для отправки POST запроса
                    fetch('{{ route("api-tester-clear-logs") }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                        .then(response => response.json())
                        .then(result => {
                            if (result.status === 'ok') {
                                admin.toastr.success('Логи успешно очищены');
                                updateLogsCount(); // Обновляем счетчик после очистки
                            } else {
                                admin.toastr.error('Ошибка при очистке логов: ' + (result.message || 'Неизвестная ошибка'));
                            }
                        })
                        .catch(error => {
                            console.error('Error clearing logs:', error);
                            admin.toastr.error('Ошибка при очистке логов');
                        });
                }
            });
        }

        // Обработчик для кнопки просмотра логов (открытие в новом окне)
        var viewLogsBtn = document.querySelector('.view-logs-btn');
        if (viewLogsBtn) {
            viewLogsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.open(this.href, '_blank', 'width=800,height=600,scrollbars=yes');
                return false;
            });
        }

        // Обработчик для кнопки скачивания логов
        var downloadLogsBtn = document.querySelector('.download-logs-btn');
        if (downloadLogsBtn) {
            downloadLogsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Открываем ссылку в новом окне для скачивания
                window.open(this.href, '_blank');

                return false;
            });
        }

        // Функция для обновления счетчика логов
        function updateLogsCount() {
            var badge = document.querySelector('.logs-count');
            if (!badge) return;

            badge.textContent = 'Загрузка...';

            // Используем fetch вместо admin.ajax.get
            fetch('{{ route("api-tester-logs") }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'ok') {
                        var count = result.count || 0;
                        badge.textContent = 'Логов: ' + count;
                        badge.className = 'badge logs-count ' + (count > 0 ? 'bg-primary' : 'bg-secondary');
                    } else {
                        badge.textContent = 'Ошибка загрузки';
                        badge.className = 'badge logs-count bg-danger';
                    }
                })
                .catch(error => {
                    console.error('Failed to load logs count:', error);
                    badge.textContent = 'Ошибка загрузки';
                    badge.className = 'badge logs-count bg-danger';
                });
        }

    })();
</script>

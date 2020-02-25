(function(w, $) {
    'use strict';

    // register the main object
    w.LaravelExplorer = function(options) {
        // options
        this.options = $.extend({}, {
            baseUrl: '/',
            csrfToken: null,
            width: '70%',
            autoWidth: true,
            height: '80%',
            autoHeight: true,
            title: 'Laravel Explorer',
            closable: true,
            spinner: false,
            spinnerIcon: '<span class="spinner-border" role="status"></span>',
            closeByBackdrop: true,
            closeByKeyboard: true,
            onSelected: function(files){},
        }, options);

        // store main dialog
        this.mainDialog = null;
    };

    // private methods
    function mergeUrl(url1, url2)
    {
        if (!url2.length) {
            return url1;
        }

        if (url1.length && url1.substr(url1.length - 1) != '/') {
            url1 += '/';
        }

        return (url1 + url2);
    }

    function loading(mainDialogRef)
    {
        // start spinner, disable buttons
        mainDialogRef.set('spinner', true).getButtons().prop('disabled', true);
    }

    function loaded(mainDialogRef)
    {
        // stop spinner, enable buttons
        mainDialogRef.set('spinner', false).getButtons().prop('disabled', false);
    }

    function fail(message)
    {
        // todo: display error message here
        console.log(message);
    }

    function bindToItems(_this, mainDialogRef)
    {
        // change directory, breadcrumb
        $(mainDialogRef.getModalBody()).find('.laravel-explorer .breadcrumb .breadcrumb-item a').on('click', function() {
            changeDirectory(_this, mainDialogRef, $(this).attr('data-id'));
        });

        // change directory, content directories
        $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .directory .square').on('dblclick', function() {
            changeDirectory(_this, mainDialogRef, $(this).parent().attr('data-id'));
        });

        // select item(s)
        $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .square').on('click', function(e) {
            if (!e.ctrlKey) {
                $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .square').removeClass('selected');
            }

            if (e.ctrlKey) {
                $(this).toggleClass('selected');
            } else {
                $(this).addClass('selected');
            }
        });

        // deselect item(s)
        $(mainDialogRef.getModalBody()).on('click', function(e) {
            var exceptions = $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .square, .laravel-explorer .btn-toolbar button');

            if (!exceptions.is(e.target) && exceptions.has(e.target).length == 0) {
                $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .square').removeClass('selected');
            }
        });

        // choose file
        $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .file .square').on('dblclick', function() {
            // call onSelected callback
            _this.options.onSelected([$(this).parent().attr('data-id')]);

            // close dialog
            mainDialogRef.close();
        });

        // rename item (double click to input)
        $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .name input').on('dblclick', function() {
            // rename item
            $(this).prop('readonly', false);

            // store input
            var input = $(this);

            // original name (if rename fails)
            var originalName = input.val();

            // focus out, so rename it
            $(this).off('blur').on('blur', function() {
                // get item
                var item = input.parent().parent();

                // on renamed
                function renamed(name)
                {
                    // set new name
                    input.val(name);

                    // set input back to readonly
                    input.prop('readonly', true);
                }

                // on failed
                function failed()
                {
                    // set original name
                    input.val(originalName);

                    // set input back to readonly
                    input.prop('readonly', true);
                }

                // call rename
                if (item.hasClass('directory')) {
                    // rename directory
                    renameDirectory(_this, mainDialogRef, item.attr('data-id'), input.val(), function(name) {
                        // renamed
                        renamed(name);
                    }, function() {
                        // failed
                        failed();
                    });
                } else if (item.hasClass('file')) {
                    // rename file
                    renameFile(_this, mainDialogRef, item.attr('data-id'), input.val(), function(name) {
                        // renamed
                        renamed(name);
                    }, function() {
                        // failed
                        failed();
                    });
                }
            });

            // enter pressed
            $(document).off('keydown', $(this)).on('keydown', $(this), function(e) {
                // enter pressed
                if (e.which == 13) {
                    // remove focus, to do the rename
                    input.blur();
                }
            });
        });

        // rename item (click to edit button)
        $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .name button').on('click', function() {
            // find main item
            var item = $(this).closest('.item');

            // get input
            var input =  $(item).find('.name input');

            // open a new dialog to edit item's name
            SimpleBsDialog.show({
                width: '500px',
                autoWidth: false,
                height: '210px',
                autoHeight: false,
                title: 'Rename ' + ($(item).hasClass('directory') ? 'directory' : $(item).hasClass('file') ? 'file' : ''),
                closable: true,
                spinner: false,
                closeByBackdrop: true,
                closeByKeyboard: true,
                cssClass: 'laravel-explorer input-dialog',
                onShow: function(renameItemDialogRef) {
                    renameItemDialogRef.getModalBody().html('<div class="form-group row"><label for="laravel-explorer-item-name" class="col-3">Rename to</label><div class="col-9"><input type="text" class="form-control" id="laravel-explorer-item-name" value="' + input.val() + '" /></div></div>');
                },
                onShown: function(renameItemDialogRef) {
                    renameItemDialogRef.getModalBody().find('#laravel-explorer-item-name').focus();
                },
                buttons: [{
                        id: 'btn-ok',
                        label: 'OK',
                        cssClass: 'btn-primary',
                        action: function(renameItemDialogRef) {
                            // call rename
                            if (item.hasClass('directory')) {
                                // rename directory
                                renameDirectory(_this, mainDialogRef, item.attr('data-id'), renameItemDialogRef.getModalBody().find('#laravel-explorer-item-name').val(), function(name) {
                                    // renamed
                                    $(input).val(name);
                                });
                            } else if (item.hasClass('file')) {
                                // rename file
                                renameFile(_this, mainDialogRef, item.attr('data-id'), renameItemDialogRef.getModalBody().find('#laravel-explorer-item-name').val(), function(name) {
                                    // renamed
                                    $(input).val(name);
                                });
                            }

                            // close the dialog
                            renameItemDialogRef.close();
                        },
                    }, {
                        id: 'btn-cancel',
                        label: 'Cancel',
                        cssClass: 'btn-secondary',
                        action: function(renameItemDialogRef) {
                            // close the dialog
                            renameItemDialogRef.close();
                        },
                    },
                ],
            });
        });
    }

    function refresh(_this, mainDialogRef)
    {
        // loading
        loading(mainDialogRef);

        // get dir list in deeper level
        $.ajax({
            type: 'GET',
            url: mergeUrl(_this.options.baseUrl, 'refresh'),
        }).done(function(result) {
            // update content
            $(mainDialogRef.getModalBody()).find('.content').html(result.content);

            // bind to change directory
            bindToItems(_this, mainDialogRef);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // no success
            fail(jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(mainDialogRef);
        });
    }

    function changeDirectory(_this, mainDialogRef, directoryId)
    {
        // loading
        loading(mainDialogRef);

        // get dir list in deeper level
        $.ajax({
            type: 'GET',
            url: mergeUrl(_this.options.baseUrl, 'directory/'  + directoryId + '/change/'),
        }).done(function(result) {
            // update breadcrumb dirs
            $(mainDialogRef.getModalBody()).find('.breadcrumb').html(result.breadcrumb);

            // update content
            $(mainDialogRef.getModalBody()).find('.content').html(result.content);

            // update navigation up
            $(mainDialogRef.getModalBody()).find('button[data-request=up]').attr('data-id', result.parent).attr('disabled', !result.parent);

            // bind to change directory
            bindToItems(_this, mainDialogRef);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // no success
            fail(jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(mainDialogRef);
        });
    }

    function createDirectory(_this, mainDialogRef, directoryName)
    {
        // loading
        loading(mainDialogRef);

        // post directory/create
        $.ajax({
            type: 'POST',
            url: mergeUrl(_this.options.baseUrl, 'directory/create'),
            data: {
                _token: _this.options.csrfToken,
                name: directoryName,
            },
        }).done(function(result) {
            // update content
            $(mainDialogRef.getModalBody()).find('.content').html(result.content);

            // bind to change directory
            bindToItems(_this, mainDialogRef);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // no success
            fail(jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(mainDialogRef);
        });

        // return main object
        return this;
    }

    function renameDirectory(_this, mainDialogRef, directoryId, name, onRenamed, onFailed)
    {
        // loading
        loading(mainDialogRef);

        // rename directory
        $.ajax({
            type: 'POST',
            url: mergeUrl(_this.options.baseUrl, 'directory/'  + directoryId + '/rename'),
            data: {
                _token: _this.options.csrfToken,
                _method: 'PATCH',
                name: name,
            },
        }).done(function(result) {
            // call renamed callback
            if ($.isFunction(onRenamed)) {
                onRenamed(result.name);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // call failed callback
            if ($.isFunction(onFailed)) {
                onFailed();
            }

            // no success
            fail(jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(mainDialogRef);
        });
    }

    function deleteDirectories(_this, mainDialogRef, directories)
    {
        // delete directories
        return $.ajax({
            type: 'POST',
            url: mergeUrl(_this.options.baseUrl, 'directory/delete'),
            data: {
                _token: _this.options.csrfToken,
                _method: 'DELETE',
                items: directories,
            },
        }).done(function(result) {
            // success, but nothing to do here
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // no success
            fail(jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(mainDialogRef);
        });
    }

    function deleteFiles(_this, mainDialogRef, files)
    {
        // delete files
        return $.ajax({
            type: 'POST',
            url: mergeUrl(_this.options.baseUrl, 'file/delete'),
            data: {
                _token: _this.options.csrfToken,
                _method: 'DELETE',
                items: files,
            },
        }).done(function(result) {
            // success, but nothing to do here
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // no success
            fail(jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(mainDialogRef);
        });
    }

    function renameFile(_this, mainDialogRef, fileId, name, onRenamed, onFailed)
    {
        // loading
        loading(mainDialogRef);

        // rename file
        $.ajax({
            type: 'POST',
            url: mergeUrl(_this.options.baseUrl, 'file/'  + fileId + '/rename'),
            data: {
                _token: _this.options.csrfToken,
                _method: 'PATCH',
                name: name,
            },
        }).done(function(result) {
            // call renamed callback
            if ($.isFunction(onRenamed)) {
                onRenamed(result.name);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // call failed callback
            if ($.isFunction(onFailed)) {
                onFailed();
            }

            // no success
            fail(jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(mainDialogRef);
        });
    }

    function deleteItems(_this, mainDialogRef, directories, files)
    {
        // loading
        loading(mainDialogRef);

        // delete directories and/or files with promises
        var def = $.Deferred(), requests = [];

        // delete directories
        if (directories.length) {
            requests.push(deleteDirectories(_this, mainDialogRef, directories));
        }

        // delete files
        if (files.length) {
            requests.push(deleteFiles(_this, mainDialogRef, files));
        }

        $.when.apply($, requests).done(function() {
            // resolve args
            def.resolve(arguments);

            // and refresh after deletes
            refresh(_this, mainDialogRef)
        });
    }

    function show(_this, mainDialogRef)
    {
        // loading
        loading(mainDialogRef);

        // open show blade
        $.ajax({
            type: 'GET',
            url: mergeUrl(_this.options.baseUrl, ''),
        }).done(function(result) {
            // update main container
            $(mainDialogRef.getModalBody()).html(result.content);

            // map selectall button
            $(mainDialogRef.getModalBody()).find('button[data-request=selectall]').on('click', function() {
                $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .square').addClass('selected');
            });

            // map delete items button
            $(mainDialogRef.getModalBody()).find('button[data-request=deleteitems]').on('click', function() {
                // get selected directories/files
                var directories = [], files = [];

                $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item').each(function(index, item) {
                    if ($(item).find('.square').hasClass('selected')) {
                        if ($(item).hasClass('directory')) {
                            directories.push($(item).attr('data-id'));
                        }

                        if ($(item).hasClass('file')) {
                            files.push($(item).attr('data-id'));
                        }
                    }
                });

                // ask the user before delete
                SimpleBsDialog.show({
                    width: '500px',
                    autoWidth: false,
                    height: '180px',
                    autoHeight: false,
                    title: 'New folder',
                    closable: true,
                    spinner: false,
                    closeByBackdrop: true,
                    closeByKeyboard: true,
                    cssClass: 'laravel-explorer input-dialog',
                    onShow: function(deleteItemsDialogRef) {
                        if (directories.length + files.length > 0) {
                            deleteItemsDialogRef.getModalBody().html('Do you really want to delete the ' + (directories.length + files.length) + ' selected ' + (directories.length > 0 ? 'directorie(s)' : '') + (directories.length && files.length > 0 ? '/' : '') + (files.length > 0 ? 'file(s)' : '') + '?');
                        } else {
                            deleteItemsDialogRef.getModalBody().html('Please select at least one file or directory!');
                        }
                    },
                    buttons: (directories.length + files.length > 0) ? [{
                            id: 'btn-yes',
                            label: 'Yes',
                            cssClass: 'btn-primary',
                            action: function(deleteItemsDialogRef) {
                                // call delete directories/files
                                deleteItems(_this, mainDialogRef, directories, files);

                                // close the dialog
                                deleteItemsDialogRef.close();
                            },
                        }, {
                            id: 'btn-cancel',
                            label: 'Cancel',
                            cssClass: 'btn-secondary',
                            action: function(deleteItemsDialogRef) {
                                // close the dialog
                                deleteItemsDialogRef.close();
                            },
                        },
                    ] : [{
                            id: 'btn-close',
                            label: 'Close',
                            cssClass: 'btn-primary',
                            action: function(deleteItemsDialogRef) {
                                // close the dialog
                                deleteItemsDialogRef.close();
                            },
                        },
                    ],
                });
            });

            // map createdirectory button
            $(mainDialogRef.getModalBody()).find('button[data-request=createdirectory]').on('click', function() {
                SimpleBsDialog.show({
                    width: '500px',
                    autoWidth: false,
                    height: '210px',
                    autoHeight: false,
                    title: 'New folder',
                    closable: true,
                    spinner: false,
                    closeByBackdrop: true,
                    closeByKeyboard: true,
                    cssClass: 'laravel-explorer input-dialog',
                    onShow: function(createDirectoryDialogRef) {
                        createDirectoryDialogRef.getModalBody().html('<div class="form-group row"><label for="laravel-explorer-folder-name" class="col-3">Folder name</label><div class="col-9"><input type="text" class="form-control" id="laravel-explorer-folder-name" /></div></div>');
                    },
                    onShown: function(createDirectoryDialogRef) {
                        createDirectoryDialogRef.getModalBody().find('#laravel-explorer-folder-name').focus();
                    },
                    buttons: [{
                            id: 'btn-ok',
                            label: 'OK',
                            cssClass: 'btn-primary',
                            action: function(createDirectoryDialogRef) {
                                // call createDirectory
                                createDirectory(_this, mainDialogRef, createDirectoryDialogRef.getModalBody().find('#laravel-explorer-folder-name').val());

                                // close the dialog
                                createDirectoryDialogRef.close();
                            },
                        }, {
                            id: 'btn-cancel',
                            label: 'Cancel',
                            cssClass: 'btn-secondary',
                            action: function(createDirectoryDialogRef) {
                                // close the dialog
                                createDirectoryDialogRef.close();
                            },
                        },
                    ],
                });
            });

            // map uploadfile button
            $(mainDialogRef.getModalBody()).find('button[data-request=uploadfile]').on('click', function() {
                var uploadCount = 0;

                SimpleBsDialog.show({
                    width: '660px',
                    autoWidth: false,
                    height: '300px',
                    autoHeight: false,
                    title: 'Upload',
                    closable: true,
                    spinner: false,
                    closeByBackdrop: true,
                    closeByKeyboard: false,
                    cssClass: 'laravel-explorer input-dialog',
                    onShow: function(uploadDialogRef) {
                        uploadDialogRef.getModalBody().addClass('dropzone').dropzone({
                            url: mergeUrl(_this.options.baseUrl, 'file/upload'),
                            uploadMultiple: true,
                            parallelUploads: 1,
                            accept: function(file, done) {
                                done();
                            }, sending: function(file, dzXHR, formData) {
                                if (uploadCount == 0) {
                                    // disable close button befor first file begins to upload
                                    uploadDialogRef.set('closable', false).getButton('btn-close').prop('disabled', true);
                                }

                                // add csrfToken to form
                                formData.append('_token', _this.options.csrfToken);
                            }, success: function(file, result) {
                                // success
                                uploadCount++;
                            }, error: function(file, message, dzXHR) {
                                // let also dropzone to handle the message
                                this.defaultOptions.error(file, 'An error occured! (' + file.name + ')');

                                // no success
                                fail(message);
                            }, queuecomplete: function() {
                                // enable close button
                                uploadDialogRef.set('closable', true).getButton('btn-close').prop('disabled', false);
                            },
                        });
                    },
                    buttons: [{
                            id: 'btn-close',
                            label: 'Close',
                            cssClass: 'btn-primary',
                            action: function(uploadDialogRef) {
                                // call refresh
                                if (uploadCount) {
                                    refresh(_this, mainDialogRef);
                                }

                                // close the dialog
                                uploadDialogRef.close();
                            },
                        },
                    ],
                });
            });

            // map navigation up button
            $(mainDialogRef.getModalBody()).find('button[data-request=up]').on('click', function() {
                if (parseInt($(this).attr('data-id')) > 0) {
                    changeDirectory(_this, mainDialogRef, $(this).attr('data-id'));
                }
            });

            // map refresh button
            $(mainDialogRef.getModalBody()).find('button[data-request=refresh]').on('click', function() {
                refresh(_this, mainDialogRef);
            });

            // bind to change directory
            bindToItems(_this, mainDialogRef);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // no success
            fail(jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(mainDialogRef);
        });
    }

    // public methods
    LaravelExplorer.prototype.open = function()
    {
        // store this
        var _this = this;

        // open in a dialog
        _this.mainDialog = SimpleBsDialog.show({
            width: _this.options.width,
            autoWidth: _this.options.autoWidth,
            height: _this.options.height,
            autoHeight: _this.options.autoHeight,
            title: _this.options.title,
            closable: _this.options.closable,
            spinner: _this.options.spinner,
            spinnerIcon: _this.options.spinnerIcon,
            closeByBackdrop: _this.options.closeByBackdrop,
            closeByKeyboard: _this.options.closeByKeyboard,
            html: '',
            buttons: [{
                    id: 'btn-ok',
                    label: 'OK',
                    cssClass: 'btn-primary',
                    action: function(mainDialogRef) {
                        // get selected files
                        var files = [];

                        $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item.file').each(function(index, item) {
                            if ($(item).find('.square').hasClass('selected')) {
                                files.push($(item).attr('data-id'));
                            }
                        });

                        if (files.length) {
                            // call onSelected callback
                            _this.options.onSelected(files);
                        }

                        // close the dialog
                        mainDialogRef.close();
                    },
                }, {
                    id: 'btn-cancel',
                    label: 'Cancel',
                    cssClass: 'btn-secondary',
                    action: function(mainDialogRef) {
                        // close the dialog
                        mainDialogRef.close();
                    },
                },
            ],
            onShow: function(mainDialogRef) {
            },
            onShown: function(mainDialogRef) {
                // call show function
                show(_this, mainDialogRef);
            },
            onHide: function(mainDialogRef) {
            },
            onHidden: function(mainDialogRef) {
            },
        });


        // return main object
        return this;
    }

    LaravelExplorer.prototype.close = function()
    {
        // close main dialog
        this.mainDialog.close();
    }

    // for lazy people
    LaravelExplorer.show = function(options)
    {
        return (new LaravelExplorer(options)).open();
    }
}(window, jQuery));
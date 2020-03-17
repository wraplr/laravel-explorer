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
            selectMultiple: true,
            onSelected: function(files){},
        }, options);

        // reference to main dialog
        this.mainDialog = null;

        // actual file info list
        this.fileInfoList = [];

        // error counts
        this.errorCount = 0;
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

    function loading(_this, mainDialogRef)
    {
        // start spinner, disable buttons
        mainDialogRef.set('spinner', true).getButtons().prop('disabled', true);
    }

    function loaded(_this, mainDialogRef)
    {
        // stop spinner, enable cancel button
        mainDialogRef.set('spinner', false).getButton('btn-cancel').prop('disabled', false);

        // enable ok button if selections are ok
        enableOkButton(_this, mainDialogRef);
    }

    function fail(_this, mainDialogRef, messages)
    {
        // display closable errors
        if ($.type(messages) === 'string') {
            messages = {'error': messages};
        }

        // show an alert for each error
        $.each(messages, function(i, message) {
            // get new id for error
            var errorId = _this.errorCount++;

            // display the alert
            $(mainDialogRef.getModalBody()).find('.laravel-explorer .errors').append('<div class="alert alert-danger alert-dismissible fade show" role="alert" data-error-id="' + errorId + '">' + message + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');

            // hide it after 3 seconds
            $('div[data-error-id=' + errorId + ']').delay(5000).slideUp(500, function() {
                $(this).alert('close');
            });
        });
    }

    function enableOkButton(_this, mainDialogRef)
    {
        // selected file count
        var selectedFileCount = $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .file .square.selected').length;

        // enable ok button
        mainDialogRef.getButton('btn-ok').prop('disabled', !selectedFileCount || (!_this.options.selectMultiple && selectedFileCount > 1));
    }

    function bindToItems(_this, mainDialogRef)
    {
        // change directory, breadcrumb
        $(mainDialogRef.getModalBody()).find('.laravel-explorer .breadcrumb .breadcrumb-item a').on('click', function() {
            changeDirectory(_this, mainDialogRef, $(this).attr('data-id'), 'breadcrumb');
        });

        // change directory, content directories
        $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .directory .square').on('dblclick', function() {
            changeDirectory(_this, mainDialogRef, $(this).closest('.item').attr('data-id'), 'change');
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

            enableOkButton(_this, mainDialogRef);
        });

        // deselect item(s)
        $(mainDialogRef.getModalBody()).on('click', function(e) {
            var exceptions = $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .square, .laravel-explorer .btn-toolbar button');

            if (!exceptions.is(e.target) && exceptions.has(e.target).length == 0) {
                $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .square').removeClass('selected');
            }

            enableOkButton(_this, mainDialogRef);
        });

        // choose file
        $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .file .square').on('dblclick', function() {
            // store selected item
            var selectedSquare = this;

            // deselect other items, but this
            $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .square').each(function(i, square) {
                if (square != selectedSquare) {
                    $(square).removeClass('selected');
                }
            });

            // get fileInfo from id
            var fileInfo = getFileInfo(_this, parseInt($(this).closest('.item').attr('data-id')));

            // call onSelected callback
            _this.options.onSelected(_this.options.selectMultiple ? [fileInfo] : fileInfo);

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
                var item = input.closest('.item');

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
                }],
            });
        });
    }

    function getFileInfo(_this, fileId)
    {
        var fileInfoResult = null;

        // search for file info in file info list
        $.each(_this.fileInfoList, function(i, fileInfo) {
            if (fileInfo.id == fileId) {
                fileInfoResult = fileInfo;
            }
        });

        return fileInfoResult;
    }

    function getSelectedDirectories(mainDialogRef)
    {
        var selectedDirectories = [];

        $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item').each(function(index, item) {
            if ($(item).find('.square').hasClass('selected')) {
                if ($(item).hasClass('directory')) {
                    selectedDirectories.push(parseInt($(item).attr('data-id')));
                }
            }
        });

        return selectedDirectories;
    }

    function getSelectedFiles(mainDialogRef)
    {
        var selectedFiles = [];

        $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item').each(function(index, item) {
            if ($(item).find('.square').hasClass('selected')) {
                if ($(item).hasClass('file')) {
                    selectedFiles.push(parseInt($(item).attr('data-id')));
                }
            }
        });

        return selectedFiles;
    }

    function refresh(_this, mainDialogRef)
    {
        // loading
        loading(_this, mainDialogRef);

        // get dir list in deeper level
        $.ajax({
            type: 'GET',
            url: mergeUrl(_this.options.baseUrl, 'refresh'),
        }).done(function(result) {
            // update fileInfoList
            _this.fileInfoList = result.fileInfoList;

            // update content
            $(mainDialogRef.getModalBody()).find('.content').html(result.content);

            // bind to change directory
            bindToItems(_this, mainDialogRef);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // no success
            fail(_this, mainDialogRef, jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(_this, mainDialogRef);
        });
    }

    function changeDirectory(_this, mainDialogRef, directoryId, request)
    {
        // loading
        loading(_this, mainDialogRef);

        // get dir list in deeper level
        $.ajax({
            type: 'GET',
            url: mergeUrl(_this.options.baseUrl, 'directory/'  + directoryId + '/change/' + request),
        }).done(function(result) {
            // update fileInfoList
            _this.fileInfoList = result.fileInfoList;

            // update breadcrumb dirs
            $(mainDialogRef.getModalBody()).find('.breadcrumb').html(result.breadcrumb);

            // update content
            $(mainDialogRef.getModalBody()).find('.content').html(result.content);

            // update back
            $(mainDialogRef.getModalBody()).find('button[data-request=back]').attr('data-id', result.back).prop('disabled', !result.back);

            // update forward
            $(mainDialogRef.getModalBody()).find('button[data-request=forward]').attr('data-id', result.forward).prop('disabled', !result.forward);
            
            // update navigation up
            $(mainDialogRef.getModalBody()).find('button[data-request=up]').attr('data-id', result.up).prop('disabled', !result.up);

            // bind to change directory
            bindToItems(_this, mainDialogRef);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // no success
            fail(_this, mainDialogRef, jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(_this, mainDialogRef);
        });
    }

    function createDirectory(_this, mainDialogRef, directoryName)
    {
        // loading
        loading(_this, mainDialogRef);

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
            fail(_this, mainDialogRef, jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(_this, mainDialogRef);
        });

        // return main object
        return this;
    }

    function renameDirectory(_this, mainDialogRef, directoryId, name, onRenamed, onFailed)
    {
        // loading
        loading(_this, mainDialogRef);

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
            fail(_this, mainDialogRef, jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(_this, mainDialogRef);
        });
    }

    function renameFile(_this, mainDialogRef, fileId, name, onRenamed, onFailed)
    {
        // loading
        loading(_this, mainDialogRef);

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
            fail(_this, mainDialogRef, jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(_this, mainDialogRef);
        });
    }

    function deleteItems(_this, mainDialogRef, directories, files)
    {
        // loading
        loading(_this, mainDialogRef);

        // delete directories and/or files
        return $.ajax({
            type: 'POST',
            url: mergeUrl(_this.options.baseUrl, 'item/delete'),
            data: {
                _token: _this.options.csrfToken,
                _method: 'DELETE',
                items: {
                    directories: directories,
                    files: files,
                },
            },
        }).done(function(result) {
            // success, but nothing to do here
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // no success
            fail(_this, mainDialogRef, jqXHR.responseJSON.message);
        }).always(function() {
            // refresh after delete
            refresh(_this, mainDialogRef)
        });
    }

    function copyItems(_this, mainDialogRef, directories, files)
    {
        // loading
        loading(_this, mainDialogRef);

        // copy directories and/or files
        return $.ajax({
            type: 'POST',
            url: mergeUrl(_this.options.baseUrl, 'item/copy'),
            data: {
                _token: _this.options.csrfToken,
                items: {
                    directories: directories,
                    files: files,
                },
            },
        }).done(function(result) {
            // success, enable/disable paste button
            $(mainDialogRef.getModalBody()).find('button[data-request=paste]').prop('disabled', !result.paste);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // no success
            fail(_this, mainDialogRef, jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(_this, mainDialogRef);

            // deselect all the items
            $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .square').removeClass('selected');
        });
    }

    function cutItems(_this, mainDialogRef, directories, files)
    {
        // loading
        loading(_this, mainDialogRef);

        // cut directories and/or files
        return $.ajax({
            type: 'POST',
            url: mergeUrl(_this.options.baseUrl, 'item/cut'),
            data: {
                _token: _this.options.csrfToken,
                items: {
                    directories: directories,
                    files: files,
                },
            },
        }).done(function(result) {
            // success, enable/disable paste button
            $(mainDialogRef.getModalBody()).find('button[data-request=paste]').prop('disabled', !result.paste);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // no success
            fail(_this, mainDialogRef, jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(_this, mainDialogRef);

            // deselect all the items
            $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .square').removeClass('selected');
        });
    }

    function pasteItems(_this, mainDialogRef)
    {
        // loading
        loading(_this, mainDialogRef);

        // paste directories and/or files (stored in session)
        return $.ajax({
            type: 'POST',
            url: mergeUrl(_this.options.baseUrl, 'item/paste'),
            data: {
                _token: _this.options.csrfToken,
            },
        }).done(function(result) {
            // success, enable/disable paste button
            $(mainDialogRef.getModalBody()).find('button[data-request=paste]').prop('disabled', !result.paste);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            // no success
            fail(_this, mainDialogRef, jqXHR.responseJSON.message);
        }).always(function() {
            // refresh after paste
            refresh(_this, mainDialogRef)
        });
    }

    function show(_this, mainDialogRef)
    {
        // loading
        loading(_this, mainDialogRef);

        // open show blade
        $.ajax({
            type: 'GET',
            url: mergeUrl(_this.options.baseUrl, ''),
        }).done(function(result) {
            // update fileInfoList
            _this.fileInfoList = result.fileInfoList;

            // update main container
            $(mainDialogRef.getModalBody()).html(result.content);

            // map copy items button
            $(mainDialogRef.getModalBody()).find('button[data-request=copy]').on('click', function() {
                // get selected directories/files
                var directories = getSelectedDirectories(mainDialogRef), files = getSelectedFiles(mainDialogRef);

                // check selected items count
                if (!(directories.length + files.length)) {
                    // ask the user before delete
                    SimpleBsDialog.show({
                        width: '500px',
                        autoWidth: false,
                        height: '180px',
                        autoHeight: false,
                        title: 'Copy',
                        closable: true,
                        spinner: false,
                        closeByBackdrop: true,
                        closeByKeyboard: true,
                        html: 'Please select at least one file or directory!',
                        cssClass: 'laravel-explorer input-dialog',
                        buttons: [{
                            id: 'btn-close',
                            label: 'Close',
                            cssClass: 'btn-primary',
                            action: function(deleteItemsDialogRef) {
                                // close the dialog
                                deleteItemsDialogRef.close();
                            },
                        }],
                    });
                } else {
                    // call copy items
                    copyItems(_this, mainDialogRef, directories, files);
                }
            });

            // map cut items button
            $(mainDialogRef.getModalBody()).find('button[data-request=cut]').on('click', function() {
                // get selected directories/files
                var directories = getSelectedDirectories(mainDialogRef), files = getSelectedFiles(mainDialogRef);

                // check selected items count
                if (!(directories.length + files.length)) {
                    // ask the user before delete
                    SimpleBsDialog.show({
                        width: '500px',
                        autoWidth: false,
                        height: '180px',
                        autoHeight: false,
                        title: 'Cut',
                        closable: true,
                        spinner: false,
                        closeByBackdrop: true,
                        closeByKeyboard: true,
                        html: 'Please select at least one file or directory!',
                        cssClass: 'laravel-explorer input-dialog',
                        buttons: [{
                            id: 'btn-close',
                            label: 'Close',
                            cssClass: 'btn-primary',
                            action: function(deleteItemsDialogRef) {
                                // close the dialog
                                deleteItemsDialogRef.close();
                            },
                        }],
                    });
                } else {
                    // call cut items
                    cutItems(_this, mainDialogRef, directories, files);
                }
            });

            // map paste items button
            $(mainDialogRef.getModalBody()).find('button[data-request=paste]').on('click', function() {
                // call paste items
                pasteItems(_this, mainDialogRef);
            });

            // map selectall button
            $(mainDialogRef.getModalBody()).find('button[data-request=selectall]').on('click', function() {
                $(mainDialogRef.getModalBody()).find('.laravel-explorer .content .item .square').addClass('selected');
            });

            // map delete items button
            $(mainDialogRef.getModalBody()).find('button[data-request=deleteitems]').on('click', function() {
                // get selected directories/files
                var directories = getSelectedDirectories(mainDialogRef), files = getSelectedFiles(mainDialogRef);

                // ask the user before delete
                SimpleBsDialog.show({
                    width: '500px',
                    autoWidth: false,
                    height: '180px',
                    autoHeight: false,
                    title: 'Delete',
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
                    }] : [{
                        id: 'btn-close',
                        label: 'Close',
                        cssClass: 'btn-primary',
                        action: function(deleteItemsDialogRef) {
                            // close the dialog
                            deleteItemsDialogRef.close();
                        },
                    }],
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
                    }],
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
                            uploadMultiple: false,
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
                                fail(_this, mainDialogRef, message);
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
                    }],
                });
            });

            // map navigation back button
            $(mainDialogRef.getModalBody()).find('button[data-request=back]').on('click', function() {
                if (parseInt($(this).attr('data-id')) > 0) {
                    changeDirectory(_this, mainDialogRef, $(this).attr('data-id'), 'back');
                }
            });

            // map navigation forward button
            $(mainDialogRef.getModalBody()).find('button[data-request=forward]').on('click', function() {
                if (parseInt($(this).attr('data-id')) > 0) {
                    changeDirectory(_this, mainDialogRef, $(this).attr('data-id'), 'forward');
                }
            });

            // map navigation up button
            $(mainDialogRef.getModalBody()).find('button[data-request=up]').on('click', function() {
                if (parseInt($(this).attr('data-id')) > 0) {
                    changeDirectory(_this, mainDialogRef, $(this).attr('data-id'), 'up');
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
            fail(_this, mainDialogRef, jqXHR.responseJSON.message);
        }).always(function() {
            // done
            loaded(_this, mainDialogRef);
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
                            files.push(parseInt($(item).attr('data-id')));
                        }
                    });

                    if (files.length) {
                        if (_this.options.selectMultiple) {
                            // convert file ids to fileInfoList
                            var fileInfoList = [];

                            $.each(files, function (i, fileId) {
                                fileInfoList.push(getFileInfo(_this, fileId));
                            });
                            
                            // call onSelected callback
                            _this.options.onSelected(fileInfoList);
                        } else {
                            // call onSelected callback with the first selected file
                            _this.options.onSelected(getFileInfo(_this, files[0]));
                        }
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
            }],
            onShow: function(mainDialogRef) {
                // disable buttons
                mainDialogRef.getButtons().prop('disabled', true);
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
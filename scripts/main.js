var cachedRoleOptions = null;

$.extend(true, $.hik.jtable.prototype, {
    deleteRows: function ($rows) {
        var self = this;

        if ($rows.length <= 0) {
            self._logWarn('No rows specified to jTable deleteRows method.');
            return;
        }

        if (self._isBusy()) {
            self._logWarn('Can not delete rows since jTable is busy!');
            return;
        }

        //Deleting just one row
        if ($rows.length === 1) {
            self._deleteRecordFromServer(
                    $rows,
                    function () { //success
                        self._removeRowsFromTableWithAnimation($rows);
                    },
                    function (message) { //error
                        self._showError(message);
                    }
            );

            return;
        }

        //Deleting multiple rows
        self._showBusy(self._formatString(self.options.messages.deleteProggress, 0, $rows.length));

        //This method checks if deleting of all records is completed
        var completedCount = 0;
        var isCompleted = function () {
            return (completedCount >= $rows.length);
        };

        //This method is called when deleting of all records completed
        var completed = function () {
            var $deletedRows = $rows.filter('.jtable-row-ready-to-remove');
            if ($deletedRows.length < $rows.length) {
                self._showError(self._formatString(self.options.messages.canNotDeletedRecords, $rows.length - $deletedRows.length, $rows.length));
            }

            if ($deletedRows.length > 0) {
                self._removeRowsFromTableWithAnimation($deletedRows);
            }

            self._hideBusy();
        };

        //Delete all rows
        var names = "";
        $rows.each(function () {
            var record = $(this).data('record');
            names += record.userName + "\n";
        });
        $.post("/ExecuteAction.php?language="+msgs.lang+"&action=deleteUsers", {"data": names, "dataType": "txt", "delimiterChar": ";"})
                .done(function (data) {
                    var respData = JSON.parse(data);
                    if (respData.Result === "OK") {
                        var deletedCount = 0;
                        $rows.each(function () {
                            var $row = $(this);
                            self._deleteRecordFromMyServer(
                                    $row,
                                    function () { //success
                                        ++deletedCount;
                                        ++completedCount;
                                        $row.addClass('jtable-row-ready-to-remove');
                                        self._showBusy(self._formatString(self.options.messages.deleteProggress, deletedCount, $rows.length));
                                        if (isCompleted()) {
                                            completed();
                                        }
                                    },
                                    function () { //error
                                        ++completedCount;
                                        if (isCompleted()) {
                                            completed();
                                        }
                                    },
                                    respData
                                    );
                        });
                    }
                })
                .fail(function () {
                    if (error) {
                        error(self.options.messages.serverCommunicationError);
                    }
                });
    }
});

$.extend(true, $.hik.jtable.prototype, {
    _deleteRecordFromMyServer: function ($row, success, error, url, data) {
        var self = this;
        //Check if it is already being deleted right now
        if ($row.data('deleting') === true) {
            return;
        }
        $row.data('deleting', true);
        self._trigger("recordDeleted", null, {record: $row.data('record'), row: $row, serverResponse: data});
        if (success) {
            success(data);
        }
    }
});



$("#srchType").on('change', function () {
    $("#srchInput").val("");
    if (this.value !== "role") {
        $("#srchInput").show();
        $("#srchRole").hide();
    } else {
        $("#srchInput").hide();
        $("#srchRole").show();
    }
});

$("#srchRole").on('change', function () {
    $("#srchInput").val(this.value);
});



$("#dialog-message").dialog({
    position: {my: "center", at: 'top+200', of: window},
    hide: 'fade',
    show: 'fade',
    autoOpen: false,
    resizable: false,
    height: "auto",
    width: 450,
    modal: true
});

$("#dialog-add").dialog({
    position: {my: "center", at: 'top+200', of: window},
    hide: 'fade',
    show: 'fade',
    autoOpen: false,
    resizable: false,
    height: "auto",
    width: 700,
    modal: true
});

$("#dialog-remove").dialog({
    position: {my: "center", at: 'top+200', of: window},
    hide: 'fade',
    show: 'fade',
    autoOpen: false,
    resizable: false,
    height: "auto",
    width: 700,
    modal: true
});

$("#dialog-backup").dialog({
    position: {my: "center", at: 'top+200', of: window},
    hide: 'fade',
    show: 'fade',
    resizable: false,
    autoOpen: false,
    height: "auto",
    width: "auto",
    modal: true
});


$('#addFileType input:radio').click(function () {
    if ($(this).val() === 'json') {
        $("#addDeliChar").attr('disabled', 'disabled');
        $("#dialog-add #inputExample").val('[{\n\t"userName": "user1",\n\t"fullName": "User One",\n\t"role": "default",\n\t"password": "012345"\n}, {\n\t"userName": "user2",\n\t"fullName": "User Two",\n\t"role": "default",\n\t"password": "012345"\n}]\n');
    } else if ($(this).val() === 'xml') {
        $("#addDeliChar").attr('disabled', 'disabled');
        $("#dialog-add #inputExample").val('<root>\n   <user>\n      <userName>user1</userName>\n      <fullName>User One</fullName>\n      <role>default</role>\n   <password>012345</password>\n      </user>\n   <user>\n      <userName>user2</userName>\n      <fullName>User Two</fullName>\n      <role>default</role>\n   <password>012345</password>\n      </user>\n</root>');
    } else if ($(this).val() === 'txt') {
        $("#addDeliChar").removeAttr('disabled');
        $("#dialog-add #inputExample").val('userName;Full Name;role;password\nuser1;User One;default;012345\nuser2;User Two;default;012345');
    }
});

$('#addFile').change(function (e) {
    $("#dialog-add .file-loading").show();
    var input = e.target;
    var reader = new FileReader();
    reader.onload = function () {
        var text = reader.result;
        $("#addInput").val(text);
        var ext = $("#addFile").val().split('.').pop();
        $('#addFileType input:radio[value='+ext+']').click();
        $("#dialog-add .file-loading").hide();
    };
    reader.readAsText(input.files[0]);
});

$('#bkpFileType input:radio').click(function () {
    if ($(this).val() === 'json') {
        $("#dialog-backup #bkpDeliChar").attr('disabled', 'disabled');
    } else if ($(this).val() === 'xml') {
        $("#dialog-backup #bkpDeliChar").attr('disabled', 'disabled');
    } else if ($(this).val() === 'txt') {
        $("#dialog-backup #bkpDeliChar").removeAttr('disabled');
    }
});

$('#delType input:radio').click(function () {
    if ($(this).val() === 'file') {
        $("#removeByRole").attr("disabled", true);
        $("#dialog-remove #removeFile").removeAttr("disabled");
        $("#dialog-remove .divSelType :input").removeAttr("disabled");
        $("#removeFileType").removeAttr("disabled");
        $("#removeInput").removeAttr("disabled");
    } else {
        $("#removeByRole").removeAttr("disabled");
        $("#dialog-remove #removeFile").attr("disabled", true);
        $("#dialog-remove .divSelType :input").attr("disabled", true);
        $("#removeFileType").attr("disabled", true);
        $("#removeInput").attr("disabled", true);
    }
});

$('#removeFileType input:radio').click(function () {
    if ($(this).val() === 'json') {
        $("#delDeliChar").attr('disabled', 'disabled');
        $("#removeExample").val('[{\n\t"userName": "user1"\n}, {\n\t"userName": "user2"\n}, {\n\t"userName": "user3"\n}, {\n\t"userName": "user4"\n}]');
    } else if ($(this).val() === 'xml') {
        $("#delDeliChar").attr('disabled', 'disabled');
        $("#removeExample").val('<root>\n   <user>\n      <userName>user1</userName>\n   </user>\n   <user>\n      <userName>user2</userName>\n   </user>\n   <user>\n      <userName>user3</userName>\n   </user>\n</root>');
    } else if ($(this).val() === 'txt') {
        $("#delDeliChar").removeAttr('disabled');
        $("#removeExample").val('username1\nusername2\nusername3\nusername4');
    }
});

$('#removeFile').change(function (e) {
//var openRemoveFile = function (event) {
    $("#dialog-remove .file-loading").show();
    var input = e.target;

    var reader = new FileReader();
    reader.onload = function () {
        var text = reader.result;
        $("#removeInput").val(text);
        var ext = $("#removeFile").val().split('.').pop();
        $('#removeFileType input:radio[value='+ext+']').click();
        $("#dialog-remove .file-loading").hide();
    };
    reader.readAsText(input.files[0]);
});


function openAddDialog() {
    $('#addFileType input:radio[value=json]').click();
    $("#addDeliChar").val(';');
    $("#addInput").val("");
    $("#addFile").val("");
    $("#dialog-add").dialog('open');
}

function openRemoveDialog() {
    $('#delType input:radio[value=file]').click();
    $('#removeFileType input:radio[value=json]').click();
    $("#delDeliChar").val(';');
    $("#removeInput").val("");
    $("#removeFile").val("");
    $("#dialog-remove").dialog('open');
}

function openBackupDialog() {
    $("#bkpDeliChar").val(';');
    $('#bkpFileType input:radio[value=json]').click();
    $("#dialog-backup").dialog('open');
}

function openMessageDialog(title, mode, msg, log){
	$("#dialog-message").dialog('option', 'title', title);
	$("#dialog-message .dialogOpt").hide();
	if (mode === "wait"){
		$("#genWait").show();
		$('#dialog-message').dialog('option', 'buttons', null);
		// $('#dialog-message').dialog('option', 'height', 230);			
	} else {
		var myButtons = {};
		myButtons[msgs.closeBtnLbl] = function () { $(this).dialog("close"); };
		$('#dialog-message').dialog({ buttons: myButtons });
		if (mode === 'resp') {
			$("#responseBox").val(log);
			$("#genSuccessMsg").text(msg);
			$("#genResp").show();
		} else if (mode === "error") {
			$("#genErroMsg").text(msg);
			$("#genErro").show();
		} else if (mode === "down") {
			$("#genDown a").attr("href", msg);
			$("#genDown").show();
		}
	}
	$("#dialog-message").dialog('open');
}



$('#LoadRecordsButton').click(function (e) {
    e.preventDefault();
    $('#UserTableContainer').jtable('load', {
        filterType: $("#srchType").val(),
        value: $("#srchInput").val()
    });
});

//Delete selected rows
$('#DeleteAllButton').click(function () {
    var $selectedRows = $('#UserTableContainer').jtable('selectedRows');
    if ($selectedRows.length > 0) {
		var result = window.confirm(msgs.delConfirm);
		if (result == true) {
            var $selectedRows = $('#UserTableContainer').jtable('selectedRows');
            $('#UserTableContainer').jtable('deleteRows', $selectedRows);
		}
    }
});



function insertDialogBtns(){
	//Add dialog
	var myButtons = {};
	myButtons[msgs.confirmBtnLbl] = function () { addUpdateUses(); };
	myButtons[msgs.cancelBtnLbl] = function () { $(this).dialog("close"); };
	$('#dialog-add').dialog({ buttons: myButtons });
	//Remove dialog
	myButtons = {};
	myButtons[msgs.confirmBtnLbl] = function () { deleteUses(); };
	myButtons[msgs.cancelBtnLbl] = function () { $(this).dialog("close"); };
	$('#dialog-remove').dialog({ buttons: myButtons });
	//Backup dialog
	myButtons = {};
	myButtons[msgs.confirmBtnLbl] = function () { generateBackup(); };
	myButtons[msgs.cancelBtnLbl] = function () { $(this).dialog("close"); };
	$('#dialog-backup').dialog({ buttons: myButtons });
}

function addUpdateUses(){
	if ($("#addInput").val() === ''){
		openMessageDialog(msgs.errorTitle, 'error', msgs.emptyMsg);
	} else {
		var result = window.confirm(msgs.addConfirm);
		if (result === true) {
			openMessageDialog(msgs.addTitle, 'wait', '');
			var data = $("#addInput").val();
			var dataType = $('#addFileType input:checked').val();
			var delimiter = $("#addDeliChar").val();
			$.post("/ExecuteAction.php?language="+msgs.lang+"&action=addUsers", {"data": data, "dataType": dataType, "delimiterChar": delimiter})
				.done(function (data) {
                    data = data.substring(data.indexOf('{'));
					var resp = JSON.parse(data);
					if (resp.Result === "OK") {
                        var msg = msgs.addResponse.replace("%d1", resp.TotalRecordCount[0]).replace("%d2", resp.TotalRecordCount[1]);
						openMessageDialog(msgs.doneTitle, 'resp', msg, resp.Records);                            
					} else if (resp.Result === "ERROR") {
						openMessageDialog(msgs.doneTitle, 'error', resp.Message);                            
					}
				});
		}
	}
}

function getRoles(){
	openMessageDialog(msgs.roleTitle, 'wait', '');
    $.post("/ExecuteAction.php?language="+msgs.lang+"&action=loadRoles")
		.done(function (data) {
            data = data.substring(data.indexOf('{'));
			resp = JSON.parse(data);
			if (resp.Result !== 'OK') {
				openMessageDialog(msgs.roleTitle, 'error', resp.Message);
			} else {
				options = resp.Options;
				cachedRoleOptions = options;
				$.each(options, function (i, item) {
					$('[name=srchRole]').append($('<option>', {
						value: item.Value,
						text: item.DisplayText
					}));
				});
				$( "#dialog-message" ).dialog('close');
			}
		});
}

function deleteUses(){
	if ($('#delType input:checked').val() === 'role'){
		var result = window.confirm(msgs.delConfirm);
		if (result == true) {
			openMessageDialog(msgs.removeTitle, 'wait', '');
			var url = '';
			if ($("#removeByRole").val() === '') {
				url = "/ExecuteAction.php?language="+msgs.lang+"&action=deleteAllUsers";
			}
			else {
				url = "/ExecuteAction.php?language="+msgs.lang+"&action=deleteAllUsersFromRole";
			}
			$.post(url, {"role": $("#removeByRole").val()})
				.done(function (data) {
                    data = data.substring(data.indexOf('{'));
					var resp = JSON.parse(data);
					if (resp.Result === "OK") {
                        var msg = msgs.removeResponse.replace("%d1", resp.TotalRecordCount);
						openMessageDialog(msgs.doneTitle, 'resp', msg, resp.Records);                            
					} else if (resp.Result === "ERROR") {
						openMessageDialog(msgs.doneTitle, 'error', resp.Message);                            
					}
			});
		}
	}
	else {
		if ($("#removeInput").val() === ''){
			openMessageDialog(msgs.errorTitle, 'error', msgs.emptyMsg);
		} else {
			var result = window.confirm(msgs.delConfirm);
			if (result === true) {
				openMessageDialog(msgs.removeTitle, 'wait', '');
				var data = $("#removeInput").val();
				var dataType = $('#removeFileType input:checked').val();
				var delimiter = $("#delDeliChar").val();
				$.post("/ExecuteAction.php?language="+msgs.lang+"&action=deleteUsers", {"data": data, "dataType": dataType, "delimiterChar": delimiter})
					.done(function (data) {
						data = data.substring(data.indexOf('{'));
                        var resp = JSON.parse(data);
						if (resp.Result === "OK") {
                            var msg = msgs.removeResponse.replace("%d1", resp.TotalRecordCount)
							openMessageDialog(msgs.doneTitle, 'resp', msg, resp.Records);                            
						} else if (resp.Result === "ERROR") {
							openMessageDialog(msgs.doneTitle, 'error', resp.Message);                            
						}
					});
			}
		}
	}
}

function generateBackup(){
	openMessageDialog(msgs.bkpTitle, 'wait', '');
	$.post("/ExecuteAction.php?language="+msgs.lang+"&action=backupUsers", {"role": $("#bkpRole").val(), "type": $('#bkpFileType :checked').val(), "delimiterChar": $("#bkpDeliChar").val()})
		.done(function (data) {
            data = data.substring(data.indexOf('{'));
			var resp = JSON.parse(data);
			if (resp.Result === "OK") {
				openMessageDialog(msgs.doneTitle, 'down', resp.Link);                            
			} else if (resp.Result === "ERROR") {
				openMessageDialog(msgs.doneTitle, 'error', resp.Message);                            
			}
		});
}



$(document).ready(function () {
    //Prepare jTable
    $('#UserTableContainer').jtable({
        title: msgs.tableTitle,
        paging: true,
        sorting: false,
        selecting: true,
        multiselect: true,
        selectingCheckboxes: true,
        //defaultSorting: 'userName ASC',
        actions: {
            listAction: 'ExecuteAction.php?language='+msgs.lang+'&action=loadUsers',
            createAction: 'ExecuteAction.php?language='+msgs.lang+'&action=addUser',
            updateAction: function (postData) {
                var id = postData.substring(3, postData.indexOf("&"));
                var $row = $('#UserTableContainer').jtable('getRowByKey', id);
                var name = $row.data('record').userName;
                postData += '&name=' + name;
                return $.Deferred(function ($response) {
                    $.ajax({
                        url: 'ExecuteAction.php?language='+msgs.lang+'&action=updateUser',
                        type: 'POST',
                        dataType: 'json',
                        data: postData,
                        success: function (data) {
                            $response.resolve(data);
                        },
                        error: function () {
                            $response.reject();
                        }
                    });
                });
            },
            deleteAction: function (postData) {
                var $row = $('#UserTableContainer').jtable('getRowByKey', postData.id);
                var record = $row.data('record');
                return $.Deferred(function ($dfd) {
                    $.ajax({
                        url: 'ExecuteAction.php?language='+msgs.lang+'&action=deleteUserByName',
                        type: 'POST',
                        dataType: 'json',
                        data: {"userName": record.userName},
                        success: function (data) {
                            $dfd.resolve(data);
                        },
                        error: function () {
                            $dfd.reject();
                        }
                    });
                });
            }
        },
        fields: {
            id: {
                key: true,
                create: false,
                edit: false,
                list: false
            },
            userName: {
                title: msgs.userNameLbl,
                width: '20%'
            },
            fullName: {
                title: msgs.fullNameLbl,
                width: '30%'
            },
            role: {
                title: msgs.roleLbl,
                width: '20%',
                options: function () {
                    if (cachedRoleOptions) { //Check for cache
                        return cachedRoleOptions;
                    }
                    var options = [];
                    $.ajax({//Not found in cache, get from server
                        url: '/ExecuteAction.php?language='+msgs.lang+'&action=loadRoles',
                        type: 'POST',
                        dataType: 'json',
                        async: false,
                        success: function (data) {
                            if (data.Result !== 'OK') {
								$("#genErroMsg").text(data.Message);
                                $("#genErro").show();
                                return;
                            }
                            options = data.Options;
                        }
                    });
                    return cachedRoleOptions = options; //Cache results and return options
                }
            },
            password: {
                title: msgs.passwordLbl,
                //width: '20%'
                list: false
            }
        }
    });

	$("#srchType").val('role');
	$("#srchRole").val('');
	$("#srchInput").val('');
    $("#srchInput").hide();
    $("#srchRole").show();
	insertDialogBtns();
	
    getRoles();
    //Load person list from server
    //$('#UserTableContainer').jtable('load');
});

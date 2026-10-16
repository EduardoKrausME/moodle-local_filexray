/**
 * File X-Ray AJAX report controls.
 *
 * @module     local_filexray/reports
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification', 'core/modal_delete_cancel', 'core/modal_events'],
    function(Ajax, Notification, ModalDeleteCancel, ModalEvents) {
    'use strict';

    var invoke = function(name, args) {
        return Ajax.call([{methodname: 'local_filexray_' + name, args: args}])[0];
    };

    var cell = function(tr, content) {
        var td = document.createElement('td');
        td.textContent = content === null || content === undefined ? '' : String(content);
        tr.appendChild(td);
        return td;
    };

    var date = function(value) {
        return value ? new Date(value * 1000).toLocaleString() : '';
    };

    var initIndex = function() {
        var container = document.querySelector('[data-region="filexray-index"]');
        if (!container) {
            return;
        }
        var button = container.querySelector('#filexray-generate');
        if (!button) {
            return;
        }
        // Use a Moodle modal instead of the browser's confirm/alert dialogs.
        var warning = container.querySelector('[data-region="operation-warning"]');
        var clearWarning = function() {
            if (warning) {
                warning.hidden = true;
                warning.textContent = "";
            }
        };
        var showBusyWarning = function(error) {
            if (!warning || !error ||
                    (error.errorcode !== "reportbusy" && error.errorcode !== "reportsbusy")) {
                return false;
            }
            warning.textContent = error.errorcode === "reportbusy" ?
                container.dataset.reportBusy : container.dataset.reportsBusy;
            warning.hidden = false;
            warning.focus();
            return true;
        };
        var modalClass = ModalDeleteCancel.default || ModalDeleteCancel;
        var events = ModalEvents.default || ModalEvents;
        container.addEventListener('click', function(e) {
            var trigger = e.target.closest('[data-action="delete-report"]');
            if (!trigger || !container.contains(trigger)) {
                return;
            }
            var reportid = Number(trigger.dataset.reportid);
            clearWarning();
            modalClass.create({
                title: container.dataset.deleteTitle + ' #' + reportid,
                body: container.dataset.deleteBody,
            }).then(function(modal) {
                modal.setDeleteButtonText(container.dataset.deleteLabel);
                modal.getRoot().on(events.delete, function() {
                    trigger.disabled = true;
                    invoke('delete_report', {reportid: reportid}).then(function() {
                        window.location.reload();
                    }).catch(function(error) {
                        trigger.disabled = false;
                        if (!showBusyWarning(error)) {
                            Notification.exception(error);
                        }
                    });
                });
                modal.show();
                return modal;
            }).catch(Notification.exception);
        });
        button.addEventListener('click', function() {
            clearWarning();
            button.disabled = true;
            invoke('create_report', {}).then(function(result) {
                window.location.assign(container.dataset.reportUrl + '?id=' + encodeURIComponent(result.reportid));
                return result;
            }).catch(function(error) {
                button.disabled = false;
                if (!showBusyWarning(error)) {
                    Notification.exception(error);
                }
            });
        });
    };

    var initReport = function(reportid) {
        var wrapper = document.querySelector('[data-region="filexray-report"]');
        if (!wrapper) {
            return;
        }
        var tbody = wrapper.querySelector('[data-region="rows"]');
        var table = wrapper.querySelector('[data-region="course-table"]');
        var previous = wrapper.querySelector('[data-action="previous"]');
        var next = wrapper.querySelector('[data-action="next"]');
        var resume = wrapper.querySelector('[data-action="resume"]');
        var offset = 0;
        var limit = 50;
        var total = 0;
        var active = true;
        var busy = false;
        var polltimer = null;

        var text = function(region, value) {
            var element = wrapper.querySelector('[data-region="' + region + '"]');
            if (element) {
                element.textContent = String(value);
            }
        };

        var render = function(data) {
            total = data.total;
            // A finished report opens its comparison automatically.
            if (data.status === 'completed' || data.status === 'partial') {
                active = false;
                window.location.replace(wrapper.dataset.comparisonUrl);
                return;
            }
            text('summary', (data.completed + data.failed) + ' / ' + data.total);
            text('percent', data.percent + '%');
            wrapper.querySelector('[data-region="progress"]').value = data.percent;
            text('completed', data.completed);
            text('failed', data.failed);
            text('files', data.filecount);
            text('range', data.total ? (offset + 1) + ' - ' +
                Math.min(offset + data.courses.length, data.total) + ' / ' + data.total : '0 / 0');
            previous.disabled = offset === 0;
            next.disabled = offset + limit >= total;
            tbody.replaceChildren();
            data.courses.forEach(function(course) {
                var tr = document.createElement('tr');
                var td = document.createElement('td');
                var anchor = document.createElement('a');
                anchor.textContent = course.name + ' (#' + course.courseid + ')';
                anchor.href = wrapper.dataset.courseUrl + '?id=' +
                    encodeURIComponent(reportid) + '&coursejobid=' + encodeURIComponent(course.id);
                td.appendChild(anchor);
                tr.appendChild(td);
                cell(tr, table.dataset[course.status] || course.status);
                cell(tr, course.filecount);
                cell(tr, date(course.started));
                cell(tr, date(course.finished));
                cell(tr, course.error);
                tbody.appendChild(tr);
            });
            active = data.status !== 'completed' && data.status !== 'partial';
        };

        var refresh = function() {
            if (busy) {
                return;
            }
            busy = true;
            invoke('report_status', {reportid: reportid, offset: offset, limit: limit})
                .then(function(response) {
                    render(response);
                    busy = false;
                    if (active) {
                        polltimer = window.setTimeout(refresh, 3000);
                    }
                    return response;
                }).catch(function(error) {
                    busy = false;
                    Notification.exception(error);
                    polltimer = window.setTimeout(refresh, 10000);
                });
        };

        var requestNow = function() {
            if (polltimer !== null) {
                window.clearTimeout(polltimer);
            }
            refresh();
        };

        previous.addEventListener('click', function() {
            offset = Math.max(0, offset - limit);
            requestNow();
        });
        next.addEventListener('click', function() {
            if (offset + limit < total) {
                offset += limit;
                requestNow();
            }
        });
        if (resume) {
            resume.addEventListener('click', function() {
                resume.disabled = true;
                invoke('resume_report', {reportid: reportid}).then(function() {
                    // Refresh report state after requesting a retry.
                    window.location.reload();
                }).catch(function(error) {
                    resume.disabled = false;
                    Notification.exception(error);
                });
            });
        }
        refresh();
    };

    return {
        initIndex: initIndex,
        initReport: initReport
    };
});

var SKIPPED = "skipped";
var APPROVED = "approved";
var BUGGED = "bug";
var FALSE_POSITIVE = "false-positive";
var PENDING = "pending";
var CHECKPOINT_STATUSES = SKIPPED + " " + APPROVED + " " + BUGGED + " " + FALSE_POSITIVE + " " + PENDING;
var skippedCount, approvedCount, bugCount, falsePositiveCount, pendingCount, checkpointChart, slider;
var projects = [], createMetadata, availableFields;
var jiraCheckpoint = null;
var newFileUrl;

const BACKEND_URL = 'http://' + window.location.host;
const BUG = 1, IMPROVEMENT = 4, LEGACY = 52,
        SUB_BUG = 8, SUB_CHANGE = 55, SUB_LEGACY = 10000;
const ISSUE_TYPES = BUG + ',' + IMPROVEMENT + ',' + LEGACY + ',' + SUB_BUG + ',' + SUB_CHANGE + ',' + SUB_LEGACY;

jQuery.fn.visible = function () {
  return this.css('visibility', 'visible');
};

jQuery.fn.invisible = function () {
  return this.css('visibility', 'hidden');
};

$(document).ready(function () {
  slider = $('.my-slider');
  $('#btn-save').click(function () {
    save();
  });
  $('#btn-saveas-pop').popover().on('shown.bs.popover', function () {
    $('#btn-saveas').click(function () {
      var filename = $('#input-filename')[0].value;
      save(filename).success(function () {
        showAlert($('#main-alert-container'), "Redirecting to new report...", 'success');
        setTimeout(function () {
          window.location.href = newFileUrl;
        }, 2000);
      });
    })
  });
  $('.panel-heading').click(function () {
    $(this).parent().find('.panel-collapse').collapse('toggle')
  });
  $('.panel-collapse')
          .on('hide.bs.collapse', function () {
            $(this).parent().find('.panel-heading .glyphicon').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
          })
          .on('show.bs.collapse', function () {
            $(this).parent().find('.panel-heading .glyphicon').removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
          });

  $('.zoom').click(function () {
    $(this).parent().parent().find('.thumbnail > .zoom > .snapshot').each(function () {
      var content = '<li class="text-center">\n'
              + '<span>' + $(this).prop('alt') + '</span>\n'
              + '<img class="img-responsive center-block slider-img" src="' + $(this).prop('src') + '">'
              + '</li>';
      if ($.contains(slider.find('ul'), $('li'))) {
        slider.find('ul > li').last().after(content);

      } else {
        slider.find('ul').append(content);
      }
    });
    slider.unslider({
      speed: 0,
      nav: true,
      arrows: true
    });
    $('#myModal').modal('show');
  });
  $('#myModal').on('hidden.bs.modal', function () {
    slider.find('ul > li').remove();
    $('.my-slider').unwrap();
    $('.unslider-arrow').unbind().remove();
    $('.unslider-nav').unbind().remove();
  })

  $('.open-jira').click(function () {
    jiraCheckpoint = $(this).parent().parent();
    if ($('#jira-issue-images').length) {
      loadJiraSnapshots(jiraCheckpoint);
    }
    $('#jira-modal').modal('show');

  });

  $(".checkpoint." + SKIPPED + " > .panel-collapse").collapse('hide');
  $(".checkpoint." + APPROVED + " > .panel-collapse").collapse('hide');
  $('.zoom').zoom()
          .on('mouseenter', triggerSiblingsZoom)
          .on('mouseleave', triggerSiblingsZoom)
          .on('mousemove', triggerSiblingsZoom);

  $(".filter-button").click(function () {
    $(".filter-button").removeClass("active");
    $(this).addClass("active");
    var status = $(this).data("filter-status");
    filterCheckpoints(status);
  });
  $('.checkpoint.skipped > div > div > button').addClass('disabled');
  $("button[data-set-status]").not('.checkpoint.skipped > div > div > button').click(function () {
    var $checkpoint = $(this).parent().parent().parent();
    var status = $(this).data("set-status");
    $checkpoint.removeClass(CHECKPOINT_STATUSES);
    $checkpoint.addClass(status);
    updateCheckpointsCount();
  });
  $("button[data-set-status]").not('.checkpoint.skipped > div > div > button').dblclick(function () {
    var $collapsible = $(this).parent().parent();
    $collapsible.collapse('hide');
  });
  loadLoginForm();
  updateCheckpointsCount();
});

function triggerSiblingsZoom(e) {
  var $target = $(e.currentTarget);
  $target.parent().parent().find('.zoom').not(e.currentTarget).each(function () {
    var event = $.Event(e.type + '.zoom');
    var xOffset = $target.offset().left - $(this).offset().left
    var yOffset = $target.offset().top - $(this).offset().top
    event.pageX = e.pageX - xOffset;
    event.pageY = e.pageY - yOffset;
    $(this).trigger(event);
  })
}

function loadJiraSnapshots($checkpoint) {
  $('#jira-issue-images').empty();
  $checkpoint.find('img.snapshot').each(function () {
    var description = $(this).prop('alt');
    var src = $(this).prop('src');
    var type = $(this).attr('value');
    $('#jira-issue-images').append('<li data-snapshot-type="' + type + '">'
            + '<label><input type="checkbox" class="center-block">' + description + '</label>'
            + '<img class="img-responsive thumbnail issue-thumbnail" src="' + src + '">'
            + '</li>')
  });
}

function jiraAlert(data, status) {
  var body = data.responseText;
  var messages = '<p>' + body + '</p>';
  var $alertContainer = $('#jira-alert-container');
  $alertContainer.append(
          '<div class="alert alert-danger alert-dismissible fade in" role="alert">'
          + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
          + messages
          + '</div>');

}

function showAlert($container, content, status) {
  $container.append(
          '<div class="alert alert-' + status + ' alert-dismissible fade in" role="alert">'
          + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
          + '<p>' + content + '</p>'
          + '</div>');
}

function getProjects(username, password) {
  return jQuery.getJSON(BACKEND_URL + '/jira/login/' + username + '/' + password + '/', null, function (data, status) {
    var jiraProject = $('#input-jira-project')[0].selectize
    if (status == "success") {
      jiraProject.clearOptions();
      data.forEach(function (proj) {
        jiraProject.addOption({
          value: proj.key,
          text: proj.name
        });
      });
      updateAvailableLabels();
    }
  });
}

function updateCreateMetadata(project) {
  updateParentIssues(project);
  updateFieldsOptions(project).done(function () {
    updateJiraFormFields($('#input-jira-issue-type')[0].selectize.getValue());
  });
}

function updateParentIssues(project) {
  if (project === "")
    return;
  jQuery.getJSON(BACKEND_URL + '/jira/issue/list/' + project + '/', null, function (data, status) {
    if (status == "success") {
      var parentIssue = $('#input-jira-parent')[0].selectize;
      parentIssue.clearOptions();
      parentIssue.addOption({
        value: "",
        text: "None"
      })
      data.forEach(function (issue) {
        parentIssue.addOption({
          value: issue.id,
          text: issue.key
        });
      });
    }
  });
}

function updateFieldsOptions(project) {
  return jQuery.getJSON(BACKEND_URL + '/jira/issue/meta/' + project + '/', null, function (data, status) {
    if (status == "success") {
      createMetadata = data;
      var bugFields = data['1'];
      updateAvailableComponents(bugFields.components.allowedValues);
      updateAvailableVersions(bugFields.versions.allowedValues);
      updateAvailableBugTypes(bugFields.customfield_10310.allowedValues);
    }
  });
}

function updateAvailableComponents(components) {
  var jiraComponents = $('#input-jira-components')[0].selectize
  jiraComponents.clearOptions();
  components.forEach(function (component) {
    jiraComponents.addOption({
      value: component.id,
      text: component.name
    });
  });
}

function updateAvailableVersions(versions) {
  var jiraAffects = $('#input-jira-affects')[0].selectize
  jiraAffects.clearOptions();
  versions.forEach(function (version) {
    jiraAffects.addOption({
      value: version.id,
      text: version.name
    });
  });
}

function updateAvailableBugTypes(types) {
  var jiraBugTypes = $('#input-jira-bug-type')[0].selectize
  jiraBugTypes.clearOptions();
  types.forEach(function (type) {
    jiraBugTypes.addOption({
      value: type.id,
      text: type.value
    });
  });
}

function updateAvailableLabels() {
  console.log('updating available labels');
  jQuery.getJSON(BACKEND_URL + '/jira/labels/', null, function (data, status) {
    if (status == "success") {
      var jiraLabels = $('#input-jira-labels')[0].selectize
      jiraLabels.clearOptions();
      data.suggestions.forEach(function (label) {
        jiraLabels.addOption({
          value: label.label,
          text: label.label
        });
      });
    }
  });

}

function updateAvailableProjects() {
  var jiraProject = $('#input-jira-project')[0].selectize
  jiraProject.clearOptions();
  projects.forEach(function (proj) {
    jiraProject.addOption({
      value: proj.key,
      text: proj.name
    });
  });
  updateAvailableLabels();
}

function filterCheckpoints(status) {
  if (status) {
    $(".checkpoint").not("." + status).slideUp();
    $(".checkpoint." + status).slideDown();
    $(".checkpoint > .panel-collapse").collapse('show');
  } else {
    $(".checkpoint").slideDown();
  }
}

function shadeRGBColor(color, percent) {
  var f = color.split(","), t = percent < 0 ? 0 : 255, p = percent < 0 ? percent * -1 : percent, R = parseInt(f[0].slice(4)), G = parseInt(f[1]), B = parseInt(f[2]);
  return "rgb(" + (Math.round((t - R) * p) + R) + "," + (Math.round((t - G) * p) + G) + "," + (Math.round((t - B) * p) + B) + ")";
}

function getCheckpointsData() {
  var skippedColor = $('button[data-filter-status="' + SKIPPED + '"]').css('background-color');
  var approvedColor = $('button[data-filter-status="' + APPROVED + '"]').css('background-color');
  var bugColor = $('button[data-filter-status="' + BUGGED + '"]').css('background-color');
  var falsePositiveColor = $('button[data-filter-status="' + FALSE_POSITIVE + '"]').css('background-color');
  var pendingColor = $('button[data-filter-status="' + PENDING + '"]').css('background-color');


  return {
    labels: [
      "Skipped",
      "Approved",
      "Bug",
      "False positive",
      "Pending"
    ],
    datasets: [
      {
        data: getCheckpointsCount(),
        backgroundColor: [
          skippedColor,
          approvedColor,
          bugColor,
          falsePositiveColor,
          pendingColor
        ],
        hoverBackgroundColor: [
          shadeRGBColor(skippedColor, 0.5),
          shadeRGBColor(approvedColor, 0.5),
          shadeRGBColor(bugColor, 0.5),
          shadeRGBColor(falsePositiveColor, 0.5),
          shadeRGBColor(pendingColor, 0.5)
        ]
      }]
  };
}


function updateCheckpointsCount() {
  var skipped = $('.checkpoint.' + SKIPPED).length;
  var approved = $('.checkpoint.' + APPROVED).length;
  var bug = $('.checkpoint.' + BUGGED).length;
  var falsePositive = $('.checkpoint.' + FALSE_POSITIVE).length;
  var pending = $('.checkpoint.' + PENDING).length;

  var sum = skipped + approved + bug + falsePositive + pending;

  var approvedPercent = 100 * approved / sum;
  var skippedPercent = 100 * skipped / sum;
  var bugPercent = 100 * bug / sum;
  var falsePositivePercent = 100 * falsePositive / sum;
  var pendingPercent = 100 * pending / sum;

  $('.progress-bar-approved').width(approvedPercent + '%').html(approvedPercent.toFixed(2) + '%');
  $('.progress-bar-false-positive').width(falsePositivePercent + '%').html(falsePositivePercent.toFixed(2) + '%');
  $('.progress-bar-bug').width(bugPercent + '%').html(bugPercent.toFixed(2) + '%');
  $('.progress-bar-skip').width(skippedPercent + '%').html(skippedPercent.toFixed(2) + '%');
  $('.progress-bar-pending').width(pendingPercent + '%').html(pendingPercent.toFixed(2) + '%');
}

function loadLoginForm(show) {
  $('#jira-modal').load('assets/html/jiraLogin.html', null, function () {
    $('#btn-jira-login').click(doLogin);
    $('#input-jira-username,#input-jira-password').keydown(function (e) {
      if (e.which == 13) { // enter key pressed
        doLogin();
      }
    })
    if (show) {
      $('#jira-modal').fadeTo(500, 1);
    }
  });
}

function doLogin() {
  var $loading = $('#btn-jira-login > .loading');
  $loading.visible();
  var username = $('#input-jira-username').val();
  var password = $('#input-jira-password').val();
  var data = {username: username, password: password};
  console.log(data);
  jQuery.ajax({
    url: BACKEND_URL + '/jira/login',
    type: "POST",
    dataType: "json", // expected format for response
    contentType: "application/json", // send as JSON
    data: JSON.stringify(data)})
          .done(function (data) {
            projects = data;
            $('#jira-modal').fadeOut(500, function () {
              loadCreateForm()
            });
          })
          .fail(jiraAlert)
          .always(function () {
            $loading.invisible();
          });
}

function loadCreateForm() {
  $('#jira-modal').load('assets/html/jiraForm.html', null, function () {
    $('#btn-jira-logout').click(function () {
      $('#jira-modal').fadeOut(500, function () {
        loadLoginForm(true);
      });
    });
    $('#input-jira-project').selectize({placeholder: "Project", onChange: updateCreateMetadata});
    $('#input-jira-parent').selectize({placeholder: "Parent task"});
    $('#input-jira-bug-type').selectize({placeholder: "Bug type"});
    $('#input-jira-reported-by').selectize({placeholder: "Reported by"});
    $('#input-jira-issue-type').selectize({placeholder: "Issue type", onChange: updateJiraFormFields});
    $('#input-jira-dev-phase').selectize({placeholder: "Development Phase"});
    $('#input-jira-priority').selectize({placeholder: "Priority"});
    $('#input-jira-affects').selectize({placeholder: "Affets Version(s)"});
    $('#input-jira-components').selectize({
      placeholder: "Components",
      plugins: ['remove_button']
    });
    $('#input-jira-labels').selectize({
      placeholder: "Labels",
      persist: false,
      plugins: ['remove_button', 'restore_on_backspace'],
      create: function (input) {
        return {
          value: input,
          text: input
        }
      }

    });
    $('#btn-jira-create-issue').click(createFormSubmit);
    loadJiraSnapshots(jiraCheckpoint);
    updateAvailableProjects();
    $('#jira-modal').fadeTo(500, 1);
  });
}

function createFormSubmit() {
  var $loading = $(this).find('.loading');
  $loading.visible();


  var fields = {};

  //create json with field values
  $('[data-field-key]').filter(":visible").each(function () {
    var key = $(this).data('field-key');
    var type = $(this).data('value-type');

    if ($(this).has('select[multiple]').length) {
      var values = $(this).children('select')[0].selectize.getValue();
      fields[key] = getMultiValueField(values, type);
    } else if ($(this).has('select').length) {
      var value = $(this).children('select')[0].selectize.getValue();
      fields[key] = getSingleValueField(value, type);
    } else {
      var value = $(this).children('input,textarea')[0].value;
      fields[key] = getSingleValueField(value, type);
    }
  })


  var data = {
    jiraIssue: {"fields": fields},
    paths: {}
  }

//  add img paths to json
  $('#jira-issue-images > li').each(function () {
    if ($(this).find('input:checked').length > 0) {
      var type = $(this).data('snapshot-type');
      var src = $(this).children('img').prop('src');
      data.paths[type] = src;
    }
  });

// POST json
  jQuery.ajax({
    url: BACKEND_URL + '/jira/issue/create/',
    contentType: 'application/json',
    data: JSON.stringify(data),
    type: 'POST',
  })
          .fail(jiraAlert)
          .always(function () {
            $loading.invisible();
          })
          .done(addIssueComment);

}

function addIssueComment(issue) {
  jiraCheckpoint.find('.comment p')
          .append('<br>- Jira issue <b>' + issue.key + '</b>: <a href="' + issue.link + '" contenteditable="false">' + issue.summary + '<a>');
  if (issue.hasOwnProperty('error')) {
    jiraCheckpoint.find('.comment p')
            .append(' <span class="text-danger">(' + issue.error + ')</span>');
  }
  $('#jira-modal').modal('hide');
}

function getSingleValueField(data, type) {
  if (type == 'string')
    return data;
  else
    var obj = {}
  obj[type] = data;
  return obj;
}

function getMultiValueField(data, type) {
  if (type == 'string')
    return data;
  else {
    var values = [];
    data.forEach(function (value) {
      values.push(getSingleValueField(value, type));
    })
    return values;
  }
}


function updateJiraFormFields(issueType) {
  availableFields = createMetadata[issueType];
  $('[data-field-key]').each(function () {
    var key = $(this).data('field-key');
    if (availableFields.hasOwnProperty(key)) {
      $(this).show();
    } else {
      $(this).hide();
    }
  });
}

$(window).scroll(function () {
  if ($(document).scrollTop() > 0) {
    $('.header').addClass('header-small');
  } else {
    $('.header').removeClass('header-small');
  }
});

function save(filename) {
  $('.popover').remove();
  var data = {
    "html": $('html').html(),
    "path": window.location.pathname
  };
  if (typeof filename !== 'undefined') {
    newFileUrl = data.path.replace(/[^\/]+\.html/, filename + '.html');
    data.path = newFileUrl;
  }
  console.log(data);
  return jQuery.ajax({
    url: BACKEND_URL + '/save',
    type: "POST",
    contentType: "application/json", // send as JSON
    data: JSON.stringify(data)})
          .done(function () {
            showAlert($('#main-alert-container'), "changes saved", 'success');
          })
          .fail(function () {
            showAlert($('#main-alert-container'), "failed to save changes", 'danger');
          });

}

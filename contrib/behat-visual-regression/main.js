"use strict";
var fs = require('fs');
var express = require('express');
var serveIndex = require('serve-index')
var serveStatic = require('serve-static')
var JiraClient = require('jira-connector');
var bodyParser = require('body-parser');
var jsonParser = bodyParser.json();

const JIRA_CLOUD_HOST = process.env.JIRA_CLOUD_HOST ? process.env.JIRA_CLOUD_HOST : "jiracloud.cit.com.br";
const JIRA_API_HOST = process.env.JIRA_API_HOST ? process.env.JIRA_API_HOST : "wsgateway.cit.com.br";
const APP_TOKEN = process.env.APP_TOKEN ? process.env.APP_TOKEN : false;
const PORT = 3000;
const STATIC_FILES_FOLDER = "/www";
const BUG = 1, IMPROVEMENT = 4, LEGACY = 52,
        SUB_BUG = 8, SUB_CHANGE = 55, SUB_LEGACY = 10000;
const ISSUE_TYPES = BUG + ',' + IMPROVEMENT + ',' + LEGACY + ',' + SUB_BUG + ',' + SUB_CHANGE + ',' + SUB_LEGACY;


//Globals
var jira, projects;

var app = express();

// Serve directory indexes for public/ftp folder (with icons) 
// Serve URLs like /ftp/thing as public/ftp/thing 
app.use('/browse', serveIndex(STATIC_FILES_FOLDER, {'icons': true}))
app.use('/browse', serveStatic(STATIC_FILES_FOLDER, {'icons': true}))

app.use(function (req, res, next) {
  res.header("Access-Control-Allow-Origin", "*");
  res.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
  next();
});

app.use(bodyParser.json()); // for parsing application/json

app.post('/save', function (req, res) {
  var data = req.body;
  var path = toLocalPath(data.path);
  fs.writeFile(path, data.html, function (err) {
    if (err) {
      res.status(500).send(err);
      console.log(err);
    } else {
      res.sendStatus(200);
    }
  });
})

app.post('/jira/login', function (req, res) {
  if (!req.body.username || !req.body.username) {
    res.status(401).send("invalid credentials");
    return;
  }
  var username = req.body.username;
  var password = req.body.password;
  jira = new JiraClient({
    host: JIRA_API_HOST,
    basic_auth: {
      username: username + '@ciandt.com',
      password: password
    }
  });

  var originalMakeRequest = jira.makeRequest.bind(jira);
  jira.makeRequest = function (options, callback, successString) {
    if (APP_TOKEN != false) {
      if (typeof options == 'undefined' || !options){
        options = {};
      }
      if (typeof options.headers == 'undefined' || !options.headers){
        options.headers = {};
      }
      options.headers['app_token'] = APP_TOKEN;
    }
    originalMakeRequest(options, callback, successString);
  }

  jira.project.getAllProjects(null, function (error, projects) {
    if (error) {
      console.log(error);
      res.status(401).send("invalid credentials");
      return;
    }
    var response = [];
    projects.forEach(function (proj) {
      response.push({key: proj.key, name: proj.name});
    })
    res.status(200).send(response);
  });

})

app.get('/jira/issue/meta/:project', function (req, res) {
  jira.issue.getCreateMetadata(
          {
            projectKeys: req.params.project,
            issuetypeIds: ISSUE_TYPES,
            expand: "projects.issuetypes.fields"
          }, function (error, data) {
    var issueTypeFields = {}
    data.projects[0].issuetypes.forEach(function (issueType) {
      issueTypeFields[issueType.id] = issueType.fields;
    })
    res.send(issueTypeFields);
  })
});

app.get('/jira/labels/:query*?', function (req, res) {
  var query = req.params.query ? req.params.query : "";
  var options = {
    uri: jira.buildURL('/labels/suggest.json?query=' + query).replace("2", "1.0"),
    method: 'GET',
    json: true,
    followAllRedirects: true
  };
  jira.makeRequest(options, function (error, data) {
    res.send(data);
  });
});

app.get('/jira/issue/list/:project', function (req, res) {
  jira.search.search({
    jql: "project = " + req.params.project
            + " & status not in ('Closed', Cancelled)"
            + " & issuetype not in subtaskIssueTypes()",
    fields: ["key"],
    maxResults: 1000
  }, function (error, data) {
    if (error) {
      res.sendStatus(501);
    } else {
      res.send(data.issues);
    }
  });
});

app.post('/jira/issue/create', function (req, res) {
  var jiraData = req.body.jiraIssue;
  if (!jiraData.fields.labels.includes("VisualRegression")) {
    jiraData.fields.labels.push("VisualRegression");
  }
  console.log(jiraData);
  var paths = req.body.paths;

  jira.issue.createIssue(jiraData, function (error, data) {
    console.log(error);
    console.log(data);
    if (error) {
      res.status(400).send(error.errors);
    } else {
      var issue = {
        link: 'https://' + JIRA_CLOUD_HOST + '/browse/' + data.key,
        summary: jiraData.fields.summary,
        key: data.key
      };

//    if there are no snapshots to attach, return immediately
      if (Object.keys(paths).length === 0 && paths.constructor === Object) {
        res.status(200).send(issue);
      } else {
        var attachments = {
          issueKey: data.key,
          filename: []
        };

        for (var path in paths) {
          if (paths.hasOwnProperty(path)) {
            attachments.filename.push(toLocalPath(paths[path]));
          }
        }
        jira.issue.addAttachment(attachments, function (error, data) {
          if (error) {
            issue['error'] = 'failed attaching snapshots';
          }
          res.status(200).send(issue);
        });
      }
    }
  });

})

function createIssue(req) {

}

app.listen(PORT, function () {
  console.log('Example app listening on port ' + PORT + '!')
})

process.on('SIGINT', function () {
  process.exit();
});

process.on('SIGTERM', function () {
  process.exit();
});

function toLocalPath(fsPath) {
  return STATIC_FILES_FOLDER + fsPath.match(/.*\/browse(\/.+)/)[1];
}
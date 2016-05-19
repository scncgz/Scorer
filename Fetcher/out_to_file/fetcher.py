#!/usr/bin/python3
# -*- coding: utf-8 -*-
import requests
import time
import xmltodict
import sys
import json
import configparser
import threading
import pymongo
from pymongo import MongoClient

conf = configparser.ConfigParser()
conf.read("fetcher.conf")

# Remote Server Configuration
server = conf.get("remote", "server")
encoding = conf.get("remote", "encoding")

# Local Server Configuration
fake_mac = conf.get("local", "fake_mac")

# Account Configuration
account = list()
senior1 = json.loads(conf.get("account", "senior1"))
senior2 = json.loads(conf.get("account", "senior2"))
senior3 = json.loads(conf.get("account", "senior3"))
#account.append(senior1)
#account.append(senior2)
account.append(senior3)


url = "http://" + server + "/"
subjects_map = {}
classes_map = {}
questions_map = {}

result = {}

class Teacher:
    username = ""
    password = ""
    logged = False

    subjects = []

    def __init__(self, username, password):
        self.username = username
        self.password = password
        self.subjects = list()

    def login(self):
        login_request = url + "ValidateUser?UserId=" +\
                        self.username + "&Password=" +\
                        self.password + "&OperMac=" +\
                        fake_mac + "&Type=D&Version=7,0,43,0"
        rs = get_dict(login_request)
        self.logged = (rs['User']['@Login'] == 'T')
        if self.logged:
            p("[" + self.username + "]Logged in as [" +
              self.username + "]!")
        else:
            p("[" + self.username + "]Error logging in as [" +
              self.username + "]!")
        return self.logged

    def getSubjects(self):
        global subjects_map
        if not self.logged:
            return False
        subject_query = url + "QuerySubject?UserId=" +\
                        self.username + "&OperMac=" + fake_mac
        rs = get_dict(subject_query)
        # For more than one subjects
        rows = []
        if type(rs['QuerySubject']['Row']) == list:
            for subject in rs['QuerySubject']['Row']:
                rows.append(subject)
        else:
            rows.append(rs['QuerySubject']['Row'])
        # Get subjects this teacher has, and add to the global map
        for d in rows:
            for (_, subject) in d.items():
                subject = subject.split(':')
                subject_id = subject[0]
                subject_name = subject[1]
                if len(subject_id) == 0:
                    continue
                if subject_id in subjects_map:
                    continue
                subjects_map[subject_id] = Subject(subject_id, subject_name)
                self.subjects.append(subject_id)
        p("[" + self.username + "]Fetched " + str(len(self.subjects)) +
          " subject(s)!")
        return len(self.subjects)

    def getClasses(self):
        global subjects_map, classes_map
        if not self.logged:
            return False
        for subject in self.subjects:
            class_query = url + "QueryClass?SubjectId=" +\
                          subject + "&OperMac=" + fake_mac
            rs = get_dict(class_query)['QueryClass']['Row']
            for class_ in rs:
                class_ = class_['Class'].split(':')
                class_id = class_[0]
                class_name = class_[1]
                if len(class_id) == 0:
                    continue
                if class_id not in classes_map:
                    classes_map[class_id] = Class(class_id, class_name)
                if class_id not in subjects_map[subject].classes:
                    subjects_map[subject].classes.append(class_id)
                if subject not in classes_map[class_id].subjects:
                    classes_map[class_id].subjects.append(subject)
            p("[" + self.username + "]Got " +
              str(len(subjects_map[subject].classes)) + " class(es)!")
        return True

    def getQuestions(self):
        global questions_map, subjects_map
        if not self.logged:
            return False
        for subject in self.subjects:
            question_query = url + "QueryQuestion?SubjectId=" +\
                             subject + "&OperMac=" + fake_mac
            rs = get_dict(question_query)['Questions']['Row']
            for question in rs:
                question_id = question['QuestionId']
                question_name = question['QuestionName']
                if len(question_id) == 0:
                    continue
                if question_id not in questions_map:
                    cut_query = url + "GetCutParam?Questionid=" +\
                                question_id + "&OperMac=" + fake_mac
                    rs = get_dict(cut_query)['Param']
                    questions_map[question_id] = Question(question_id, question_name)
                    questions_map[question_id].cut = rs['Cut']
                    questions_map[question_id].paper = rs['Paper']
                if question_id not in subjects_map[subject].questions:
                    subjects_map[subject].questions.append(question_id)
            p("[" + self.username + "]Got " +
              str(len(subjects_map[subject].questions)) + " question(s)!")
        return True

    def fetch(self):
        global result
        if not self.logged:
            return False
        for subject in self.subjects:
            p("[" + self.username + "]Started fetching {Subject} " +
              "(" + subjects_map[subject].name + ")" +
              str(subject) + "!")
            for class_ in subjects_map[subject].classes:
                p("[" + self.username +
                  "]Started fetching {Class} " +
                  str(class_) + "(" + classes_map[class_].name +
                  ")" + "!")
                for question in subjects_map[subject].questions:
                    p("[" + self.username +
                      "]Started fetching {Question} " +
                      str(question) + "(" +
                      questions_map[question].name + ")!")
                    stu_count = 0
                    score_query = url + "QueryStQuestion?Questionid=" +\
                                  question + "&ClassId=" +\
                                  class_ + "&OperMac=" + fake_mac
                    rs = get_dict(score_query)['StudentQuestions']['Row']
                    for stu in rs:
                        if stu['StudentId'] not in result:
                            result[stu['StudentId']] = Student(stu['StudentId'], stu['Name'], class_)
                        result[stu['StudentId']].score[question] = stu['Lastmark']
                        stu_count = stu_count + 1
                    p("[" + self.username + "]Got " +
                      str(stu_count) + " student(s)!")
        p("[" + self.username + "]Task finished! Got " +
          str(len(result)) + " students by now!")



class Student:
    id = 0
    name = ""
    class_ = 0
    score = {}

    def __init__(self, id, name, class_):
        self.score = dict()
        self.id = id
        self.name = name
        self.class_ = class_

    def zip(self):
        rs = dict()
        rs["name"] = self.name
        rs["id"] = self.id
        rs["class"] = self.class_
        rs["score"] = self.score
        return rs

class Subject:
    id = 0
    name = ""
    questions = []
    classes = []

    def __init__(self, id, name):
        self.id = id
        self.name = name
        self.questions = list()
        self.classes = list()

class Question:
    id = 0
    name = ""
    cut = ""
    paper = ""

    def __init__(self, id, name):
        self.id = id
        self.name = name

class Class:
    id = 0
    name = ""
    subjects = []

    def __init__(self, id, name):
        self.id = id
        self.name = name
        self.subjects = list()

def p(msg):
    msg = time.strftime("[%Y-%m-%d %X] ", time.localtime()) + msg
    print(msg)


def die(msg):
    p(msg)
    sys.exit(0)


def get(address):
    try:
        rs = requests.get(address)
        rs.encoding = encoding
        return rs.text
    except:
        return None


def get_dict(address):
    rs = get(address)
    rs = xmltodict.parse(rs)
    while not rs or rs == None:
        p("An error occurred! Retrying...")
        time.sleep(0.5)
        rs = get(address)
        rs = xmltodict.parse(rs)
    return rs


def test():
    p("Testing connections...")
    rs = get(url)
    if not rs:
        die("Remote server error!")
    for group in account:
        for (username, password) in group.items():
            t = Teacher(username, password)
            if not t.login():
                die("Teacher[" + username + "] Not Okay!")
    p("Test OK!")
    p("Press enter key to continue...")
    a = input()

def zip_subject():
    rs = list()
    for (_, subject) in subjects_map.items():
        s = dict()
        s["id"] = subject.id
        s["name"] = subject.name
        s["questions"] = subject.questions
        s["classes"] = subject.classes
        rs.append(s)
    return rs

def zip_class():
    rs = list()
    for (_, class_) in classes_map.items():
        c = dict()
        c["id"] = class_.id
        c["name"] = class_.name
        c["subjects"] = class_.subjects
        rs.append(c)
    return rs

def zip_question():
    rs = list()
    for (_, question) in questions_map.items():
        q = dict()
        q["id"] = question.id
        q["name"] = question.name
        q["cut"] = question.cut
        q["paper"] = question.paper
        rs.append(q)
    return rs

test()

start_time = time.time()

col_name = str(int(time.time()))
col_score_name = "score_" + col_name
col_subject_name = "subject_" + col_name
col_class_name = "class_" + col_name
col_question_name = "question_" + col_name
col_score_file = open(col_score_name, 'w')
col_subject_file = open(col_subject_name, 'w')
col_class_file = open(col_class_name, 'w')
col_question_file = open(col_question_name, 'w')

threads = {}
for group in account:
    for (username, password) in group.items():
        t = Teacher(username, password)
        t.login()
        t.getSubjects()
        t.getClasses()
        t.getQuestions()
        threads[username] = threading.Thread(target=t.fetch, name=username)
        threads[username].start()
for (_, thread) in threads.items():
    thread.join()

p("The collection name is [" + col_name + "]")

# Add extra info
p("Adding s/c/q info to the files...")
col_subject_file.write(json.dumps(zip_subject()))
col_class_file.write(json.dumps(zip_class()))
col_question_file.write(json.dumps(zip_question()))
col_subject_file.close()
col_class_file.close()
col_question_file.close()
p("Finished!")

# Final task
rs = list()
p("Recording " + str(len(result)) + " Students...")
for (_, student) in result.items():
    stu = student.zip()
    rs.append(stu)
col_score_file.write(json.dumps(rs))
col_score_file.close()

end_time = time.time()

p("Finished! It cost " + str(end_time - start_time) + " seconds.")
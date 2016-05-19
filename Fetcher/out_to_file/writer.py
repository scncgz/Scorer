#!/usr/bin/python3
# -*- coding: utf-8 -*-
import json
import configparser
import os
import pymongo
from pymongo import MongoClient

conf = configparser.ConfigParser()
conf.read("fetcher.conf")

# Local Server Configuration
db_server = conf.get("local", "db_server")

mongodb = MongoClient(db_server)
db = mongodb.scncgz_score
print("We have connected to your MongoDB server.")

print("Input the timestamp:")
col_name = input()

col_score_name = "score_" + col_name
col_subject_name = "subject_" + col_name
col_class_name = "class_" + col_name
col_question_name = "question_" + col_name
col_score = db[col_score_name]
col_subject = db[col_subject_name]
col_class = db[col_class_name]
col_question = db[col_question_name]

if not os.path.exists(col_score_name):
    print('File not Existing')
    exit()
col_score_file = open(col_score_name, 'r')
col_subject_file = open(col_subject_name, 'r')
col_class_file = open(col_class_name, 'r')
col_question_file = open(col_question_name, 'r')

print("Reading files...")
col_score_data = json.loads(col_score_file.read())
col_subject_data = json.loads(col_subject_file.read())
col_class_data = json.loads(col_class_file.read())
col_question_data = json.loads(col_question_file.read())
print("We've got " + str(len(col_score_data)) + " student, " +
      str(len(col_question_data)) + " questions and " +
      str(len(col_class_data)) + " classes.")

print("Press enter to continue...")
_ = input()

print("Adding s/c/q info to the database...")
col_subject.insert_many(col_subject_data, False)
col_class.insert_many(col_class_data, False)
col_question.insert_many(col_question_data, False)
col_score.insert_many(col_score_data, False)
print("Finished!")

print("Creating indexes...")
col_score.create_index([("id", pymongo.ASCENDING)])
print("Finished!")
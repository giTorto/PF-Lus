import os
import subprocess
import sys
from utilities import run_cmd

__author__ = 'gt'

file_tested = sys.argv[1]
file_prediction = sys.argv[2]
evaluator = sys.argv[3]

prepare_test = open("simplified_test.txt", "w")
run_cmd("cat " + file_tested + " | cut -f 1,2 ", prepare_test)
prepare_test.close()

only_pred = open("only_predictions.txt", "w")
run_cmd("cat " + file_prediction + " | cut -f 4", only_pred)
only_pred.close()

test_pred = open("real_vs_pred.txt", "w")
run_cmd("paste simplified_test.txt only_predictions.txt", test_pred)
test_pred.close()

test_pred = open("real_pred.txt", "w")
run_cmd("cat real_vs_pred.txt | sed  's/\t/ /g' | sed '/^[[:space:]]*$/d'", test_pred)
test_pred.close()

os.remove("real_vs_pred.txt")
test_pred = open("results.txt", "w")
run_cmd("perl " + evaluator + " -r -o NOEXIST < real_pred.txt", test_pred)
test_pred.close()

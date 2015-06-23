import os
import sys
import subprocess
from utilities import from_file_to_phrases,  run_cmd
from fst_utilities import from_fst_to_file, from_phrase_to_fst

__author__ = 'gt'


input_file = sys.argv[1]
type = sys.argv[2]
output_file = sys.argv[3]
train_file = sys.argv[4]

if not os.path.isfile("third.fst"):
    exit_stat = subprocess.call("python createFST.py " + train_file, shell=True)

phrases = []
if "col" in type:
    phrases = from_file_to_phrases(input_file)
else:
    new_train = open(input_file, "r")
    phrases = [x.strip() for x in new_train]
    new_train.close()


if os.path.isfile(output_file):
    os.remove(output_file)

test_result = open(output_file, 'a')
counter = 1

for phrase in phrases:
    from_phrase_to_fst(phrase, "short_sentence_compose.fst")
    from_fst_to_file("short_sentence_compose.fst", "fstprinted.txt")
    run_cmd("cat fstprinted.txt", test_result)
    print(str(counter) + " over " + str(len(phrases)))
    counter += 1

test_result.close()

#subprocess.call("python evaluate.py " + correct_labels + " " + test_result.name, shell=True)

#show_fst("short_sentence_compose.fst", "B.png")

import os
import subprocess
import sys
from utilities import run_cmd, add_labels,  count_occurrence, \
    replace_word_with_concept, create_bigrams, collapse_word_in_single_row, labels_file_to_set, \
    smooth_table, compute_probability_on_table, create_new_prob_table, remove_junk, duplicate_double_label, \
    compute_label_prob

__author__ = 'gt'

split_labels = sys.argv[1]
train_feature_file = sys.argv[2]
train_label_file = sys.argv[3]
train_file = sys.argv[4]
train_concept = sys.argv[5]
crf_template = sys.argv[6]

if not os.path.isfile("third.fst"):
    exit_stat = subprocess.call("python createFST.py " + train_file, shell=True)

if not os.path.isfile("alltest.txt"):
    subprocess.call("python3 text2fsa.py '" + train_feature_file + "' 'raw' 'alltest.txt' " + train_feature_file, shell=True)

if not os.path.isfile("crf.lm"):
    subprocess.call("crf_learn " + crf_template + " " + train_concept + " crf.lm", shell=True)


if not os.path.exists("NB"):
    os.makedirs("NB")



train_based_filename = "NB/basic.train.file.txt"
train_file = open(train_based_filename, "w")
run_cmd("cat alltest.txt | cut -f 3,4", train_file)
train_file.close()

train_file = open("NB/token.pos.crf.train.txt", "w")
run_cmd("crf_test -m crf.lm " + train_based_filename, train_file)
train_file.close()

os.remove(train_based_filename)

add_labels(train_file.name, "NB/token.pos.crf.utt.train", train_label_file)
train_based_filename = "NB/token.pos.crf.utt.train"

if split_labels == "True":
    duplicate_double_label(train_based_filename, "NB/token.pos.crf.2utt.train")
    train_based_filename = "NB/token.pos.crf.2utt.train"

count_occurrence(train_based_filename, "NB/labels.count", "3")
labels = labels_file_to_set("NB/labels.count")

#create token prob
create_new_prob_table(train_based_filename, labels, "NB/token_label_table.prob", "0")
print("token prob > DONE")


# create POS prob
create_new_prob_table(train_based_filename, labels, "NB/pos_label_table.prob", "1")
print("POS prob > DONE")

# create concept prob
create_new_prob_table(train_based_filename, labels, "NB/concept_label_table.prob", "2")
print("Concept prob > DONE")

# create token replace with concept prob
replace_word_with_concept("NB/token.pos.crf.utt.train", "NB/token+crf.pos.utt.train")
create_new_prob_table("NB/token+crf.pos.utt.train", labels, "NB/concept_xor_token_label_table.prob", "0", "2")
print("Token XOR concept prob > DONE")


# create token pos prob
count_occurrence(train_based_filename, "NB/tok_POS.count", "0,1")
count_occurrence(train_based_filename, "NB/tok_POS_label.count", "0,1,3")
with open("NB/tok_POS.count", "r") as f:
    with open("NB/token_POS.count", "w") as outp:
        for line in f:
            line = line.replace('\t', ' ', 1)
            outp.write(line)

with open("NB/tok_POS_label.count", "r") as f:
    with open("NB/token_POS_label.count", "w") as outp:
        for line in f:
            line = line.replace('\t', ' ', 1)
            outp.write(line)
collapse_word_in_single_row("NB/token_POS_label.count", labels, "NB/token_POS_all_label.count")
smooth_table("NB/token_POS.count", "NB/token_POS_all_label.count", labels, "NB/token.pos.smoothed.count",
             "NB/token_pos_all_label.smoothed.count")
compute_probability_on_table("NB/token.pos.smoothed.count", "NB/token_pos_all_label.smoothed.count",
                             "NB/token_pos_label_table.prob", False)
remove_junk(["NB/tok_POS.count", "NB/tok_POS_label.count", "NB/token_POS.count", "NB/token_POS_label.count",
             "NB/token_POS_all_label.count", "NB/token_pos_all_label.smoothed.count", "NB/token.pos.smoothed.count"])
print("Token-POS prob > DONE")


# create bigrams
create_bigrams("NB/token.pos.crf.utt.train", "NB/to_bigrams.train")
create_bigrams("NB/token+crf.pos.utt.train", "NB/to_bigrams_token_xor_crf.train", False, '\t', 2)

# create bigrams of words
create_new_prob_table("NB/to_bigrams.train", labels, "NB/word_bigrams_label_table.prob", "0")
print("Word bigram prob > DONE")

# create bigrams of pos
create_new_prob_table("NB/to_bigrams.train", labels, "NB/pos_bigrams_label_table.prob", "1")
print("POS bigram prob > DONE")

# create bigrams of concept
create_new_prob_table("NB/to_bigrams.train", labels, "NB/concept_bigrams_label_table.prob", "2")
print("Concept bigram prob > DONE")

# create bigrams of word replaced with concept
create_new_prob_table("NB/to_bigrams_token_xor_crf.train", labels, "NB/concept_xor_token_bigrams_label_table.prob",
                      "0", "2")
print("token xor Concept bigram prob > DONE")
compute_label_prob("NB/concept_bigrams_label_table.prob", "NB/labels.prob", train_label_file)




"""create lexicon
  - frequency cutoff
  - remove stop-word
p(Q) = p(P) = 1/2
for each word P(h|D) = P(D|h)P(h)/P(D)
P(Q|wi) = p(wi|q)p(q)
p(wi|Q) = C(wi Q) / C(wi)

p(wi|P) = C(wi P) / C(wi)
"""

"""count_occurrence(train_based_filename, "NB/token.count")
count_occurrence(train_based_filename, "NB/token_label.count", "0,3")
compute_probability("NB/token_label.count", "NB/token.count", "NB/token_label.prob", True)

count_occurrence(train_based_filename, "NB/POS.count", "1")
count_occurrence(train_based_filename, "NB/POS_label.count", "1,3")
compute_probability("NB/POS_label.count", "NB/POS.count", "NB/pos_label.prob", True)

count_occurrence(train_based_filename, "NB/concept.count", "2")
count_occurrence(train_based_filename, "NB/concept_label.count", "2,3")
compute_probability("NB/concept_label.count", "NB/concept.count", "NB/concept_label.prob", True)
print("Token prob > DONE")

count_occurrence("NB/token+crf.pos.utt.train", "NB/token+concept.count")
count_occurrence("NB/token+crf.pos.utt.train", "NB/token+concept_label.count", "0,2")
compute_probability("NB/token+concept_label.count", "NB/token+concept.count", "NB/token+concept.prob", True)


count_occurrence(train_based_filename, "NB/tok_POS.count", "0,1")
count_occurrence(train_based_filename, "NB/tok_POS_label.count", "0,1,3")
with open("NB/tok_POS.count", "r") as f:
    with open("NB/token_POS.count", "w") as outp:
        for line in f:
            line = line.replace('\t', ' ', 1)
            outp.write(line)

with open("NB/tok_POS_label.count", "r") as f:
    with open("NB/token_POS_label.count", "w") as outp:
        for line in f:
            line = line.replace('\t', ' ', 1)
            outp.write(line)

compute_probability("NB/token_POS_label.count", "NB/token_POS.count", "NB/token_POS_label.prob", True)

count_occurrence("NB/to_bigrams.train", "NB/bigram_word.count", "0")
count_occurrence("NB/to_bigrams.train", "NB/bigram_word_label.count", "0,3")
compute_probability("NB/bigram_word_label.count", "NB/bigram_word.count", "NB/bigram_word.prob", True)


count_occurrence("NB/to_bigrams.train", "NB/bigram_pos.count", "1")
count_occurrence("NB/to_bigrams.train", "NB/bigram_pos_label.count", "1,3")
compute_probability("NB/bigram_pos_label.count", "NB/bigram_pos.count", "NB/bigram_pos.prob", True)

count_occurrence("NB/to_bigrams.train", "NB/bigram_concept.count", "2")
count_occurrence("NB/to_bigrams.train", "NB/bigram_concept_label.count", "2,3")
compute_probability("NB/bigram_concept_label.count", "NB/bigram_concept.count", "NB/bigram_concept.prob", True)

count_occurrence("NB/to_bigrams_token_xor_crf.train", "NB/bigram_concept_token.count", "2")
count_occurrence("NB/to_bigrams_token_xor_crf.train", "NB/bigram_concept_token_label.count", "2,3")
compute_probability("NB/bigram_concept_token_label.count", "NB/bigram_concept_token.count",
                    "NB/bigram_concept_token.prob", True)

"""
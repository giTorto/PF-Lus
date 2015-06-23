import math
import os
import sys
from utilities import run_cmd, count_occurrence, compute_probability
from fst_utilities import fst_compile, create_lex, fst_union, text_to_FAR, create_lm, show_fst, from_fst_to_file

__author__ = 'gt'

name_train_token_pos_lemma = sys.argv[1]


if not os.path.exists("OutputData"):
    os.makedirs("OutputData")

if not os.path.exists("OutputData/count_prob"):
    os.makedirs("OutputData/count_prob")

if not os.path.exists("OutputData/intermediateFst"):
    os.makedirs("OutputData/intermediateFst")

if not os.path.exists("OutputData/intermediateSentence"):
    os.makedirs("OutputData/intermediateSentence")

# for POS
# Calculating C(ti)
count_occurrence(name_train_token_pos_lemma, "OutputData/count_prob/Pos.counts", "1")
# Calculating C(ti,wi)
count_occurrence(name_train_token_pos_lemma, "OutputData/count_prob/pos_token.count", "0,1")
# Calculating P(wi|ti) = C(ti, wi) / C(ti)
compute_probability("OutputData/count_prob/pos_token.count", "OutputData/count_prob/Pos.counts",
                    "OutputData/count_prob/token_pos.prob")

# Creating FST
pos_token_prob = open("OutputData/count_prob/token_pos.prob", "r")
pos_token_fst = open("OutputData/intermediateFst/token_pos_fst.prefst", "w")

for line in pos_token_prob:
    stringa = "0\t0\t" + line
    pos_token_fst.write(stringa)

pos_token_fst.write("0")
pos_token_fst.close()
pos_token_prob.close()



# Creating Unknown FST

number_of_tokens = 0
pos = open("OutputData/count_prob/Pos.counts", "r")

for lines in pos:
    number_of_tokens += 1

pos.seek(0)
unknw_fst = open("OutputData/intermediateFst/unkwn_fst.txt", "w")

for lines in pos:
    words = lines.split("\t")
    unknw_fst.write("0\t0\t<unk>\t" + words[0] + "\t" + str(-1 * math.log(1.0 / float(number_of_tokens))) + "\n")

unknw_fst.write("0")
unknw_fst.close()
pos.close()

create_lex(name_train_token_pos_lemma, "all.lex")


fst_compile("OutputData/intermediateFst/token_pos_fst.prefst", "OutputData/intermediateFst/first.fst")
fst_compile("OutputData/intermediateFst/unkwn_fst.txt", "OutputData/intermediateFst/unkwn_fst.fst")
fst_union("OutputData/intermediateFst/unkwn_fst.fst", "OutputData/intermediateFst/first.fst",  "second.fst")
new_file = open("third.fst", "w")
run_cmd("fstclosure second.fst", new_file)
new_file.close()

# Pos pharases created
pos_phrase = open("OutputData/count_prob/pos_sentence.txt", "w")
run_cmd(
    "cat " + name_train_token_pos_lemma +
    " | cut -f 2 | sed 's/^ *$/#/g' | tr '\n' ' ' | tr '#' '\n' | sed 's/^ *//g;s/ *$//g' ",
    pos_phrase)
pos_phrase.close()

text_to_FAR("OutputData/count_prob/pos_sentence.txt", "OutputData/intermediateFst/pos.far")
create_lm("OutputData/intermediateFst/pos.far", "OutputData/intermediateFst/pos.lm", 4)

"""
# for Lemmas
count_occurrence(name_train_token_pos_lemma, "OutputData/count_prob/Lemmas.counts", "2")
# Calculating C(ti,wi)
count_occurrence(name_train_token_pos_lemma, "OutputData/count_prob/lemmas_token.count", "0,2")
# Calculating P(wi|ti) = C(ti, wi) / C(ti)
compute_probability("OutputData/count_prob/lemmas_token.count", "OutputData/count_prob/Lemmas.counts",
                    "OutputData/count_prob/lemmas_token.prob")

tokens = open("OutputData/count_prob/tokens.txt", "w")
run_cmd("cat OutputData/count_prob/lemmas_token.prob | cut -f 2", tokens)
tokens.close()
tokens = open("OutputData/count_prob/lemmas_prob.txt", "w")
run_cmd("cat OutputData/count_prob/lemmas_token.prob | cut -f 1,3", tokens)
tokens.close()
tokens = open("OutputData/count_prob/lemma_to_token.prob", "w")
run_cmd("paste OutputData/count_prob/tokens.txt OutputData/count_prob/lemmas_prob.txt", tokens)
tokens.close()


# Creating token to lemmas FST
pos_token_prob = open("OutputData/count_prob/lemma_to_token.prob", "r")
pos_token_fst = open("OutputData/intermediateFst/lemmas_token_prefst.txt", "w")

for line in pos_token_prob:
    stringa = "0\t0\t" + line
    pos_token_fst.write(stringa)

pos_token_fst.write("0")
pos_token_fst.close()
pos_token_prob.close()"""

"""# token to lemmas
fst_compile("OutputData/intermediateFst/lemmas_token_prefst.txt", "OutputData/intermediateFst/token_lemma.fst")
fst_union("OutputData/intermediateFst/unkwn_fst.fst", "OutputData/intermediateFst/token_lemma.fst",
          "OutputData/intermediateFst/tok_lemma_unk.fst")
new_file = open("tok_to_lemma.fst", "w")
run_cmd("fstclosure OutputData/intermediateFst/tok_lemma_unk.fst", new_file)
new_file.close() """
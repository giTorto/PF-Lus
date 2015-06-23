import os
import subprocess
from utilities import run_cmd, from_phrase_to_fstfile, create_bigrams

__author__ = 'gt'

def fst_compile(in_file, out_file, i_lex_file="all.lex", home_path=""):
    out_fst = open(home_path + out_file, "w")
    exit_stat = run_cmd('fstcompile --isymbols=' + home_path + i_lex_file + ' --osymbols=' + home_path + 'all.lex ' + in_file, out_fst)
    out_fst.close()

    return


def create_lex(in_file, out_lex):
    temp = open("tem.lex", "w")
    run_cmd("cat " + in_file + "| cut -f 1,2 ", temp)
    temp.close()
    lexicon_file = open(out_lex, "w")
    exit_stat = run_cmd('ngramsymbols tem.lex', lexicon_file)
    lexicon_file.close()
    return


def fst_union(first_file, second_file, out):
    out_fst = open(out, "w")
    exit_stat = run_cmd('fstunion ' + first_file + " " + second_file, out_fst)
    out_fst.close()

    return


def text_to_FAR(infile, outfile):
    out_far = open(outfile, "w")
    run_cmd("farcompilestrings --symbols=all.lex --unknown_symbol='<unk>' " + infile, out_far)
    out_far.close()
    return


def count_n_grams(in_file, out_file, num):
    out_cnt = open(out_file, "w")
    run_cmd("ngramcount --order=" + str(num) + " --require_symbols=false " + in_file, out_cnt)
    out_cnt.close()
    return


def create_lm(in_file, out_file_name, num=3, metod="witten_bell"):
    count_name = "OutputData/count_prob/pos_sen.cnt"
    count_n_grams(in_file, count_name, num)
    out_file = open(out_file_name, "w")
    run_cmd("ngrammake --method=" + metod + " " + count_name, out_file)
    return


def fst_compose(first, second, out):
    out_fst = open(out, "w")
    exit_stat = run_cmd('fstcompose ' + first + ' ' + second, out_fst)
    out_fst.close()


def show_fst(in_file, out_file, in_lex="all.lex"):
    subprocess.call("fstdraw --isymbols=" + in_lex + " --osymbols=all.lex " + in_file +
                    " | dot -Tpng > A.png",
                    shell=True)
    subprocess.call("convert A.png -rotate 90 " + out_file, shell=True)
    os.remove("A.png")
    subprocess.call("xdg-open " + out_file, shell=True)
    return

def from_file_to_fst(in_file, out_fst_name, in_lex_file, home_path=""):
    fst_compile(in_file, "OutputData/intermediateSentence/sentence.fst", in_lex_file, home_path)

    fst_compose(home_path + "OutputData/intermediateSentence/sentence.fst", home_path + "third.fst",
                home_path + "OutputData/intermediateSentence/sentence_compose.fst")


    fst_compose(home_path + "OutputData/intermediateSentence/sentence_compose.fst", home_path +"OutputData/intermediateFst/pos.lm",
                home_path + "OutputData/intermediateSentence/sentence_compose_2.fst")

    out_fst = open(out_fst_name, "w")
    exit_stat = run_cmd("fstrmepsilon " + home_path +
                        "OutputData/intermediateSentence/sentence_compose_2.fst | fstshortestpath", out_fst)
    out_fst.close()
    return

def from_phrase_to_fst(sentence, out_fst, home_path=""):
    from_phrase_to_fstfile(sentence, home_path + "OutputData/intermediateSentence/sentence.txt",
                           "sen.lex", home_path)
    from_file_to_fst(home_path + "OutputData/intermediateSentence/sentence.txt", out_fst,
                     "sen.lex", home_path)
    return

def from_fst_to_file(in_fst, out_file, in_lex="all.lex", home_path=""):
    result = open(out_file, "w")
    run_cmd("fstprint --isymbols=" + home_path + in_lex + " --osymbols=" + home_path + "all.lex " + in_fst +
            " | cat - | sort -r -n ", result)
    result.close()

    return


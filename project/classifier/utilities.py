from collections import Counter
import os
from subprocess import Popen
import math

__author__ = 'gt'

found_words = {}

def from_element_to_string(element):
    row = ""
    index = 0
    for stringa in element:
        if index < len(element)-1:
            row = row + str(stringa) + "\t"
        else:
            row = row + str(stringa) + "\n"

        index += 1

    return row


def run_cmd(cmd, logfile):
    p = Popen(cmd, shell=True, universal_newlines=True, stdout=logfile)
    ret_code = p.wait()
    logfile.flush()
    return ret_code


def count_occurrence(in_file, out_file, which_col="0", separator="\t", min_words=2):
    train_pos_file = open(in_file, "r")
    pos_count = open(out_file, "w")
    which = which_col.split(",")

    lines = []
    index = 1
    for line in train_pos_file:
        line.replace("\n", "")
        if line != "":
            words = line.split(separator)
            if len(words) >= min_words:
                to_append = ""
                for number in which:
                    if index == len(which):
                        to_append += words[int(number.strip())].strip()
                    else:
                        to_append += words[int(number.strip())].strip() + "\t"

                    index += 1

                lines.append(to_append)
                index = 1

    counter = Counter(lines)
    values = []

    for element in counter.items():
        values.append(from_element_to_string(element))

    values.sort()

    for stringA in values:
        pos_count.write(stringA)

    pos_count.close()
    train_pos_file.close()

    return


def compute_probability(input_smaller, input_bigger, out_file, reverse=False, log=True):
    pos_token_co = open(input_smaller, "r")
    pos_count = open(input_bigger, "r")
    pos_token_prob = open(out_file, "w")
    count = 0
    post = []
    for line in pos_token_co:
        words = line.split("\t")
        if words[0] == "":
            continue

        found = False
        words[2] = words[2].replace("\n", "")
        while not found:
            post = pos_count.readline().split("\t")
            if post[0] == '':
                continue

            if not reverse:
                if post[0] == words[1]:
                    count = post[1].replace("\n", "")
                    found = True
            else:
                if post[0] == words[0]:
                    count = post[1].replace("\n", "")
                    found = True

        pos_count.seek(0)

        if count == 0:
            print(words[1])
        if log:
            prob = -(math.log(float(words[2]) / float(count)))
        else:
            prob = (float(words[2]) / float(count))

        pos_token_prob.write(words[0] + "\t" + words[1] + "\t" + str(prob) + "\n")

    pos_token_prob.close()
    pos_token_co.close()
    pos_count.close()
    return


def from_file_to_phrases(input_file_name):
    input_file = open(input_file_name, "r")
    lines = []
    row = ""

    for line in input_file:
        words = line.split("\t")
        row += words[0] + " "

        if words[0] == "\n":
            lines.append(row.strip())
            row = ""

    input_file.close()
    return lines


def from_phrase_to_fstfile(sentence, out_file_name, sen_lex, home_path=""):
    words_array = sentence.split(" ")
    out_file = open(out_file_name, "w")
    o_lexicon = open(home_path + "all.lex", "r")
    in_lexicon = open(home_path + sen_lex, "w")
    global found_words

    state = 0
    for word in words_array:
        if word != "":
            word_ret = give_me_word(word, o_lexicon).split("\t")
            out_file.write(str(state) + "\t" + str(state + 1) + "\t" + word + "\t" + word_ret[0] + "\n")
            in_lexicon.write(word + "\t" + word_ret[1] + "\n")
            state += 1

    out_file.write(str(state))
    out_file.close()
    o_lexicon.close()
    in_lexicon.close()
    return



def give_me_word(word, lex):
    global found_words

    val = found_words.get(word)
    unknown_val = ""
    found = False
    if val is None:
        for line in lex:
            line = line.strip()
            words = line.split("\t")
            if word == '':
                continue

            if word == words[0]:
                found_words[word] = word + "\t" + words[1]
                val = word + "\t" + words[1]
                found = True
                break
            elif words[0] == "<unk>":
                unknown_val = words[1]

        if not found:
            found_words[word] = '<unk>' + '\t' + unknown_val
            val = '<unk>' + '\t' + unknown_val

        lex.seek(0)

    return val


def add_labels(starting_filename="token.pos.crf.train.txt", out_file_name="token.pos.crf.utt.train",
               label_file_name="InputData/NLSPARQL.train.utt.labels.txt"):
    starting_file = open(starting_filename, "r")
    label_file = open(label_file_name, "r")
    out_file_name = open(out_file_name, "w")

    for line in label_file:
        label = line.strip()
        line = starting_file.readline().strip()

        while not line == '':
            out_file_name.write(line + "\t" + label + "\n")
            line = starting_file.readline().strip()

        out_file_name.write("\n")

    return


def set_up_stop_words(stop_word_file_name="InputData/english.stop.txt"):
    stop_word_file = open(stop_word_file_name, "r")
    stop_words = set()

    lines = []
    for line in stop_word_file:
        line = line.strip()
        lines.append(line)

    stop_words = {x for x in lines}

    return stop_words


def remove_stop_words(input, output, stop_words, type="file", separator="\t"):

    if type == "file":
        in_file = open(input, "r")
        lines = []
        for line in in_file:
            words = line.split(separator)

            if not words[0] in stop_words:
                lines.append(line)

        in_file.close()

        out_file = open(output, "w")
        for line in lines:
            out_file.write(line)

        out_file.close()
        return ""
    else:
        words = input.split(" ")
        lines = ""
        for word in words:
            if not word in stop_words:
                lines += word + " "

        return lines.strip()



def replace_word_with_concept(input_file_name="NB/token.pos.crf.utt.train", out_file_name="NB/token+crf.pos.utt.train",
                              min_features=3, label=True):
    in_file = open(input_file_name, "r")
    temp_file = open(out_file_name, "w")

    for line in in_file:
        features = line.split("\t")

        if len(features) > min_features:
            if features[2].strip() != "O":
                features[0] = features[2]

            if label:
                temp_file.write(features[0] + "\t" + features[1] + "\t" + features[3])
            else:
                temp_file.write(features[0].strip() + "\t" + features[1] + "\n")

    temp_file.close()
    in_file.close()
    return


def create_bigrams(in_file, out_file, no_label=False, separator="\t", number_of_features=3, order=2):
    input_file = open(in_file, "r")
    out_file = open(out_file, "w")

    phrases = [""] * number_of_features
    previous = [""] * number_of_features
    lines = []
    numb = 0
    counter = 0
    for line in input_file:

        if line.strip() == "":
            phrases = [""] * number_of_features
            previous = [""] * number_of_features
            out_file.write("\n")
            counter = 0
            continue

        features = line.strip().split(separator)
        if len(features) < number_of_features:
            continue

        for number in range(0, number_of_features):
            phrases.insert(int(number), phrases.pop(int(number)) + features[int(number)] + " ")
            previous.pop(int(number))
            previous.insert(int(number), features[int(number)] + " ")

        counter += 1

        if counter == order:
            row = ""
            for element in phrases:
                row += element.strip() + "\t"

            if not no_label:
                out_file.write(row + features[len(features)-1] + "\n")
                numb += 1
            else:
                out_file.write(row.strip() + "\n")
                numb += 1

            counter -= 1
            phrases.clear()
            phrases.extend(previous)

    out_file.close()
    input_file.close()
    return numb


def collapse_word_in_single_row(input_filename, label_set, out_file=""):
    if out_file == "":
        out_file = input_filename

    labels = {}
    lines = []
    with open(input_filename, "r") as starting:
        prev = ""

        for line in starting:
            features = line.split("\t")
            word = features[0]

            if word == prev:
                labels[features[1]] = int(features[2].strip())
            else:
                if len(labels.keys()) > 0:
                    lines.append(count_to_row(prev, labels, label_set))
                    labels.clear()

                labels[features[1]] = int(features[2].strip())

            prev = features[0]

        if len(labels) > 0:
            lines.append(count_to_row(prev, labels, label_set))
            labels.clear()

    with open(out_file, "w") as final:
        for line in lines:
            final.write(line)

def labels_file_to_set(label_file):
    labels = set()
    lines = []

    with open(label_file, "r") as file:
            for line in file:
                lines.append(line.split("\t")[0])

    labels = {x for x in lines}

    return labels


def count_to_row(token, labels_seen, all_label):
    row = token

    for label in sorted(set(all_label)):
        if label in labels_seen:
            row += "\t" + str(labels_seen[label])
        else:
             row += "\t" + "0"

    row += "\n"

    return row

def smooth_table(token_count_file_name, token_label_table, all_label, out_file_count="", out_file_table=""):
    if out_file_count == "":
        out_file_count = token_count_file_name

    if out_file_table == "":
        out_file_table = token_label_table
    lines = []
    with open(token_label_table, "r") as table:
        for line in table:
            words = line.split("\t")
            row = words[0]
            for word in words[1:]:
                value = int(word)
                row += "\t" + str(value+1)

            row += "\n"
            lines.append(row)

    values = []
    with open(token_count_file_name, "r") as token_count:
        for line in token_count:
            words = line.split("\t")
            value = int(words[1].strip()) + len(all_label)
            values.append(words[0] + "\t" + str(value) + "\n")

    with open(out_file_table, "w") as final:
        with open(out_file_count, "w") as token_count:
            for line in lines:
                final.write(line)

            for row in values:
                token_count.write(row)

    return


def compute_probability_on_table(table, token_column, probab_table="", log=True):
    if probab_table == "":
        probab_table = table

    lines = []
    with open(table, "r") as table_count,  open(token_column, "r") as token_count:
        for line_token, line_table in zip(table_count, token_count):
            token_values = line_token.split("\t")
            overall_token_count = int(token_values[1])
            table_values = line_table.split("\t")
            row = table_values[0]
            for value in table_values[1:]:
                if log:
                    prob = -(math.log(float(value) / float(overall_token_count)))
                else:
                    prob = (float(value) / float(overall_token_count))

                row += "\t" + str(prob)

            lines.append(row + "\n")



    from_lines_to_file(lines, probab_table)

    return


def from_lines_to_file(lines, out_file):
    with open(out_file, "w") as out_fil:
        for line in lines:
            out_fil.write(line)

    return


def create_new_prob_table(train_based_filename, labels, out_file_name, cols, label_col="3"):
    which = cols.split(",")
    colums = ""
    for numb in which:
        colums += numb + ","

    count_occurrence(train_based_filename, "NB/token.count", colums[:len(colums)-1])
    count_occurrence(train_based_filename, "NB/token_label.count", colums + label_col)
    collapse_word_in_single_row("NB/token_label.count", labels, "NB/token_all_label.count")

    smooth_table("NB/token.count", "NB/token_all_label.count", labels, "NB/token.smoothed.count",
                 "NB/token_all_label.smoothed.count")
    compute_probability_on_table("NB/token.smoothed.count", "NB/token_all_label.smoothed.count", out_file_name, True)
    remove_junk(["NB/token.count", "NB/token_label.count", "NB/token_all_label.count",
                 "NB/token_all_label.smoothed.count", "NB/token.smoothed.count"])

    return

def remove_junk(lista):
    for junk in lista:
        os.remove(junk)

    return


def duplicate_double_label(input_filename, output_filename):
    index = 0
    with open(input_filename, "r") as starting, open(output_filename, "w") as output_f:
        sentence = ["", "", "", ""]
        for line in starting:
            features = line.split("\t")
            labels = features[len(features)-1].strip().split(" ")
            index = 0
            if len(labels) > 1:
                for label in labels:
                    features[len(features)-1] = label
                    sentence[index] += from_element_to_string(features)
                    index += 1

            if line.strip() != "":
                if len(labels) == 1:
                    output_f.write(line)
            else:
                if len(sentence) == 0:
                    output_f.write("\n")
                else:
                    to_write = ""
                    for row in sentence:
                        if row != "":
                            to_write += row + "\n"


                    if to_write != "\n":
                        output_f.write(to_write + "\n")
                sentence = ["", "", "", ""]


            if len(features) < 0:
                index = 0

    return


def compute_label_prob(out_table, out_label_probs, label_file="NB/labels.count"):
    #total_labels = update_after_smoothing(out_table, label_file, "NB/labels.after.bi.count")
    label_file_count = "NB/labels.new.count"
    count_occurrence(label_file, label_file_count, "0", "\t", 1)


    total_labels = 0

    with open(label_file_count, "r") as f:
        for line in f:
            total_labels += int(line.strip().split("\t")[1])

    with open(out_label_probs, "w") as lab_prob, open(label_file_count, "r") as lab_count:
            for line in lab_count:
                words = line.split("\t")
                words[1] = str((float(words[1].strip())/float(total_labels)))
                row = "\t".join(words) + "\n"
                lab_prob.write(row)

    return

def update_after_smoothing(prob_table, labels_file, update_label):
    with open(labels_file, "r") as labels, open(prob_table, "r") as prob_tab, open(update_label, "w") as outf:
        index = 0
        jndex = 0
        for l in prob_tab:
            index += 1

        for line in labels:
            jndex += 1
            words = line.split("\t")
            words[1] = str(int(words[1].strip()) + index)
            outf.write("\t".join(words) + "\n")

    return index * jndex


def set_up_labels_prob(label_file="NB/labels.prob"):
    all_labels = []
    with open(label_file, "r") as label_file:
        for line in label_file:
            words = line.split("\t")
            all_labels.append(float(words[1].strip()))

    return all_labels

def compute_max_and_mean(input_prob_file):
    max_array = []
    sum_array = []
    first_run = True
    counter = 0
    with open(input_prob_file, "r") as in_probs:
        for line in in_probs:
            probs = line.split("\t")
            probs.pop(0)
            index = 0
            counter += 1
            for prob in probs:
                if first_run:
                    max_array.append(float(prob))
                    sum_array.append(float(prob))
                else:
                    if max_array[index] > float(prob):
                        max_array[index] = float(prob)
                    sum_array[index] += float(prob)

                index += 1

            first_run = False

    sum_array = [math.exp(-x/counter) for x in sum_array]
    return [max_array, sum_array]
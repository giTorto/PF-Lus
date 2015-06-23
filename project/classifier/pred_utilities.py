import os
import math
from fst_utilities import from_phrase_to_fst, from_fst_to_file
from utilities import run_cmd, create_bigrams, replace_word_with_concept, remove_stop_words
import numpy as np

__author__ = 'gt'


def predict(sentence, ordered_labels, all_labels,  labels_prob=[], home_path="", stop_words={}, add_prior=False):
        from_phrase_to_fst(sentence, home_path + "NB/sentence.fst", home_path)
        from_fst_to_file(home_path + "NB/sentence.fst", home_path + "NB/sentence.txt", "all.lex", home_path)

        with open(home_path + "NB/sentence_complete.txt", "w") as sentence_complete:
            run_cmd("cat " + home_path + "NB/sentence.txt | cut -f 3,4", sentence_complete)

        with open(home_path + "NB/sentence_final.txt", "w") as sentence_complete:
            run_cmd("crf_test -v 1 -m " + home_path + "crf.lm " + home_path + "NB/sentence_complete.txt ", sentence_complete)

        #remove_stop_words("NB/sentence_final.txt", "NB/sentence_final.txt", stop_words)
        create_bigrams(home_path + "NB/sentence_final.txt", home_path + "NB/sentence_bigrams.txt", True)
        replace_word_with_concept(home_path + "NB/sentence_final.txt", home_path + "NB/token+crf.txt", 2, False)
        create_bigrams(home_path + "NB/token+crf.txt", home_path + "NB/token+crf.bigrams.txt", True, '\t', 2)


        sums = [look_into_this(home_path + "NB/sentence_bigrams.txt", all_labels, "bi_word", home_path),
                look_into_this(home_path + "NB/sentence_bigrams.txt", all_labels, "bi_pos", home_path)]


        results = np.sum(sums, axis=0)
        value = np.min(results)
        index = results.tolist().index(value)
        indexes = np.argpartition(results, 3)[:3]

        elem = {}
        temp = [math.exp(-x) for x in results]
        sum_of_probs = float(sum(temp))

        for ind in indexes:
            elem[ordered_labels[ind]] = math.exp(-results[ind])/sum_of_probs


        if add_prior:
            results = [math.exp(-x) for x in results]
            labels_prob = [x * x for x in labels_prob]
            #using the priors
            results = np.multiply(results, labels_prob)
            index = results.tolist().index(max(results))


        result = {}
        result["other_classes"] = elem
        result["main_class"] = str(math.exp(-value)/sum_of_probs) + "\t" + ordered_labels[index]
        return result


def look_into_this(file_to_look, all_labels, search_type, home_path=""):
    first_run = True
    sums = []
    with open(file_to_look, "r") as sentence:
        for lines in sentence:
            words = lines.split("\t")
            results = look_for_prob(words[0], search_type, all_labels, home_path)
            index = 0

            for prob_list in results:
                for prob in prob_list[1:]:
                    if first_run:
                        sums.append(float(prob))
                    else:
                        sums[index] += float(prob)

                    index += 1

                first_run = False

    if len(sums) < 1:
        sums = unknown_word(" ", all_labels, False)

    return sums


def look_for_prob(thing, type_search, all_labels, home_path=""):
    if type_search == "word":
        return search_for(thing, home_path + "NB/token_label_table.prob", all_labels)
    elif type_search == "pos":
        return search_for(thing, home_path + "NB/pos_label_table.prob", all_labels)
    elif type_search == "concept":
        return search_for(thing, home_path + "NB/concept_label_table.prob", all_labels)
    elif type_search == "bi_word":
        return search_for(thing, home_path + "NB/word_bigrams_label_table.prob", all_labels)
    elif type_search == "bi_pos":
        return search_for(thing, home_path + "NB/pos_bigrams_label_table.prob", all_labels)
    elif type_search == "bi_conc":
        return search_for(thing, home_path + "NB/concept_bigrams_label_table.prob", all_labels)
    elif type_search == "bi_conc_xor_tok":
        return search_for(thing, home_path + "NB/concept_xor_token_bigrams_label_table.prob", all_labels)
    elif type_search == "conc_xor_tok":
        return search_for(thing, home_path + "NB/concept_xor_token_label_table.prob", all_labels)

    return []


def search_for(thing, file, all_labels):
    lines = []

    with open(file, "r") as source:
        for line in source:
            words = line.split("\t")

            if words[0] == thing:
                lines.append(words)
                break

    if len(lines) < 1:
        lines.append(unknown_word(thing, all_labels))

    return lines


def unknown_word(word, all_labels, add_token=True):
    line = []
    if add_token:
        line.append(word)

    for i in range(0, len(all_labels)):
        line.append(-(math.log(1.0 / len(all_labels))))

    return line


def evaluate(pred_file, test_file_labels, test_file, result_file):
    with open("NB/temp.txt", "w") as temp, open(result_file, "w") as results:
        with open("NB/temp_complete", "w") as temp2:
            run_cmd("paste " + pred_file + " " + test_file_labels, temp)
            run_cmd("paste " + test_file + " " + temp.name, temp2)
            run_cmd("perl eval/conlleval.pl -d '\t' -r -o NOEXIST < " + temp2.name, results)
            temp.close()

    os.remove("NB/temp.txt")

def giveMeMeanOfClassification(input_file="NB/temp_complete", value_index=1, predicted_ind=2, real_ind=3, rm_char=0):
    sum_right = 0.0
    sum_wrong = 0.0
    count = 0
    mean_map = {}
    count_map = {}
    with open(input_file, "r") as in_file:
        for line in in_file:
            count += 1
            words = line.strip().split("\t")

            if len(words) < max(value_index, predicted_ind, real_ind):
                continue

            if rm_char > 0:
                if len(words[predicted_ind]) > rm_char:
                    words[predicted_ind] = words[predicted_ind][rm_char:]
                if len(words[real_ind]) > rm_char:
                    words[real_ind] = words[real_ind][rm_char:]

            if words[predicted_ind] == words[real_ind]:
                if mean_map.get(words[predicted_ind]) is None:
                    mean_map[words[predicted_ind]] = float(words[value_index])
                    count_map[words[predicted_ind]] = 1
                else:
                    mean_map[words[predicted_ind]] = float(mean_map.get(words[predicted_ind])) + float(words[value_index])
                    count_map[words[predicted_ind]] = count_map.get(words[predicted_ind]) + 1

                sum_right += float(words[value_index])
            else:
                if mean_map.get("mis_" + words[predicted_ind]) is None:
                    mean_map["mis_" + words[predicted_ind]] = float(words[value_index])
                    count_map["mis_" + words[predicted_ind]] = 1
                else:
                    mean_map["mis_" + words[predicted_ind]] = float(mean_map.get("mis_" + words[predicted_ind])) + float(words[value_index])
                    count_map["mis_" + words[predicted_ind]] = count_map.get("mis_" + words[predicted_ind]) + 1

                sum_wrong += float(words[value_index])

    sum_right = sum_right/count
    sum_wrong = sum_wrong/count
    #print("Mean CORRECTLY classified is " + str(sum_right))
    #print("Mean WRONGLY classified is " + str(sum_wrong))

    right = True

    for chiave in mean_map.keys():
        mean_map[chiave] = mean_map[chiave]/count_map[chiave]

    for chiave in mean_map.keys():
        if "mis_" not in chiave and not (mean_map.get("mis_" + chiave) is None) and \
                        mean_map.get(chiave) < mean_map.get("mis_" + chiave):
            print(chiave + " vs " + "mis_" + chiave + " = " + str(mean_map[chiave]) + " " + str(mean_map["mis_" + chiave]))
            right = False

        print(chiave + "\t" + str(mean_map[chiave]))

    if right:
        print("Results are OK")
    else:
        print("Some Misclassification has higher probabilities")


    return mean_map

def giveMeVariance(input_file="NB/temp_complete", value_index=1, predicted_ind=2, real_ind=3, rm_char=0):
    sum_map = {}
    squared_values_map = {}
    count_values = {}
    variance_map = {}
    with open(input_file, "r") as in_file:
        for line in in_file:
            words = line.strip().split("\t")

            if len(words) < max(value_index, predicted_ind, real_ind):
                continue

            if rm_char > 0:
                if len(words[predicted_ind]) > rm_char:
                    words[predicted_ind] = words[predicted_ind][rm_char:]
                if len(words[real_ind]) > rm_char:
                    words[real_ind] = words[real_ind][rm_char:]

            if words[predicted_ind] == words[real_ind]:
                if count_values.get(words[predicted_ind]) is None:
                    count_values[words[predicted_ind]] = 1
                    squared_values_map[words[predicted_ind]] = pow(float(words[value_index]), 2.0)
                    sum_map[words[predicted_ind]] = float(words[value_index])
                else:
                    count_values[words[predicted_ind]] = count_values.get(words[2]) + 1
                    squared_values_map[words[predicted_ind]] = squared_values_map.get(words[predicted_ind]) + pow(float(words[value_index]), 2.0)
                    sum_map[words[2]] = sum_map.get(words[predicted_ind]) + float(words[value_index])
            else:
                if count_values.get("mis_" + words[predicted_ind]) is None:
                    count_values["mis_" + words[predicted_ind]] = 1
                    squared_values_map["mis_" + words[predicted_ind]] = pow(float(words[value_index]), 2.0)
                    sum_map["mis_" + words[predicted_ind]] = float(words[value_index])
                else:
                    count_values["mis_" + words[predicted_ind]] = count_values.get("mis_" + words[predicted_ind]) + 1
                    squared_values_map["mis_" + words[predicted_ind]] = squared_values_map.get("mis_" + words[predicted_ind]) + pow(float(words[value_index]), 2.0)
                    sum_map["mis_" + words[predicted_ind]] = sum_map.get("mis_" + words[predicted_ind]) + float(words[value_index])

    print("Variance:")
    for chiave in count_values.keys():
        variance_map[chiave] = (squared_values_map[chiave] - (pow(sum_map[chiave], 2.0)/count_values[chiave]))/count_values[chiave]
        print(chiave + "\t" + str(variance_map.get(chiave)))

    return variance_map

""" multilable classification
  if multilabel:
        # for multilabel classification
        numb_of_features = 0
        with open("NB/sentence_bigrams.txt") as f_words:
            for line in f_words:
                numb_of_features += 1

        temp = np.divide(results, numb_of_features*2)
        newlist = np.argpartition(temp, 5)[:5]

        multi_results = ""
        results_set = []

        for element in newlist:
            results_set.append(ordered_labels[element])

        best = ordered_labels[results.tolist().index(min(results))]
        results_set = [x for x in results_set if best not in x]
        results_set = [x for x in results_set if x not in best]
        results_set.append(best)

        threshold = temp[ordered_labels.index(best)] + temp[ordered_labels.index(best)]*1/100
        final_res = []
        for element in results_set:
            if temp[ordered_labels.index(element)] < threshold:
                final_res.append(element)


        if len(final_res) < 1:
            multi_results = ordered_labels[results.tolist().index(min(results))]
        else:
            multi_results = " ".join(final_res)

        return multi_results.strip()
"""


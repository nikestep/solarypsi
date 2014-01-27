"""Place all APL walkthrough text from the Excel spreadsheets into a
PDF document.

:author: nestep
:date: April 1, 2013

"""

import xhtml2pdf
import xhtml2pdf.pisa
import xlrd

"""Notable lines of code to save:

# Use of xhtml2pdf
pdf = xhtml2pdf.pisa.CreatePDF("<html><head><title>test</title></head><body><h3>Hi Nik!</h3></body></html>", file("temp.pdf", "wb"))
if pdf.err:
    print "Error"

"""

class TrackerContentCols:
    elem = 0
    title_key = 1
    template = 2
    page_title = 3
    page_num = 4
    elem_type = 5
    paragraph_num = 6
    business_logic = 7
    trans_key = 8
    link = 9
    sort_order = 10
    bullet_point = 11

class TrackerWidgetCols:
    title_key = 0
    page_num = 1
    path = 2

class TrackerPropertiesCols:
    title_key = 0
    walkthrough_title = 1
    parent_image = 3  # Colmn 2 (C in Excel) is "hidden" and presumed empty
    teacher_image = 4
    tab = 5
    order = 6
    parent_image_caption = 7
    teacher_image_caption = 8

class TranslationCols:
    trans_key = 0
    content = 1  # English
    spanish = 2
    french = 3
    urdu = 4
    arabic = 5
    bengali = 6
    haitian = 7
    russian = 8
    korean = 9
    chinese = 10

class BubbleType:
    parent = 0
    teacher = 1
    links = 2
    
    @staticmethod
    def to_string(type):
        return {
            BubbleType.parent: "Parent Bubble",
            BubbleType.teacher: "Teacher Bubble",
            BubbleType.links: "Links Bubble"
        }[type]
    
    @staticmethod
    def from_string(type):
        return {
            'Parent Bubble': BubbleType.parent,
            'Teacher Bubble': BubbleType.teacher,
            'Links Bubble': BubbleType.links
        }[type]

class Walkthrough:
    title_key = None
    title = None
    parent_image = None
    teacher_image = None
    tab = None
    order = 0
    parent_image_caption = None
    teacher_image_caption = None
    pages = None
    
    def __init__(self, xlrd_row):
        self.title_key = xlrd_row[TrackerContentCols.title_key]
        self.title = None
        self.parent_image = None
        self.teacher_image = None
        self.tab = None
        self.order = 0
        self.parent_image_caption = None
        self.teacher_image_caption = None
        self.pages = {}
        self.add_row(xlrd_row)
    
    def add_row(self, xlrd_row):
        page_num = xlrd_row[TrackerContentCols.page_num]
        if page_num != "":
            page_num = int(page_num)
        else:
            page_num = 0
        
        if not page_num in self.pages:
            self.pages[page_num] = Page(xlrd_row)
        else:
            self.pages[page_num].add_row(xlrd_row)
    
    def add_server_image_path(self, xlrd_row):
        page_num = int(xlrd_row[TrackerWidgetCols.page_num])
        if page_num in self.pages:
            path = xlrd_row[TrackerWidgetCols.path]
            if path.endswith("png"):
                path = "../web{path}".format(path=path)
            elif path.endswith("vm"):
                path = "../web/WEB-INF/velocity{path}".format(path=path)
                self.pages[page_num].server_image_path_needs_processing = True
            self.pages[page_num].server_image_path = path
    
    def set_properties(self, xlrd_row):
        self.title = xlrd_row[TrackerPropertiesCols.walkthrough_title]
        self.parent_image = xlrd_row[TrackerPropertiesCols.parent_image]
        self.teacher_image = xlrd_row[TrackerPropertiesCols.teacher_image]
        self.tab = xlrd_row[TrackerPropertiesCols.tab]
        self.order = int(xlrd_row[TrackerPropertiesCols.order])
        self.parent_image_caption = xlrd_row[TrackerPropertiesCols.parent_image_caption]
        self.teacher_image_caption = xlrd_row[TrackerPropertiesCols.teacher_image_caption]
    
    def ordered_pages(self):
        return sorted(self.pages.items(), key=lambda p: p[1].sort_order)

class Page:
    number = 0
    template = None
    title = None
    server_image_path = None
    server_image_needs_processing = False
    sort_order = 0
    bubbles = None
    
    def __init__(self, xlrd_row):
        self.number = int(xlrd_row[TrackerContentCols.page_num])
        self.template = xlrd_row[TrackerContentCols.template]
        self.title = xlrd_row[TrackerContentCols.page_title]
        self.sort_order = xlrd_row[TrackerContentCols.sort_order]
        self.server_image_path = None
        self.server_image_needs_processing = False
        self.bubbles = {}
        self.add_row(xlrd_row)
    
    def add_row(self, xlrd_row):
        if (xlrd_row[TrackerContentCols.paragraph_num] == "" or
            int(xlrd_row[TrackerContentCols.paragraph_num]) == 1):
            self.bubbles[len(self.bubbles)] = Bubble(xlrd_row)
        else:
            # This is assuming everything is already sorted in XLS
            self.bubbles[len(self.bubbles) - 1].add_row(xlrd_row)
    
    def ordered_bubbles(self):
        return sorted(self.bubbles.items(), key=lambda b: b[1].sort_order)

class Bubble:
    type = None
    sort_order = 0
    paragraphs = None
    
    def __init__(self, xlrd_row):
        self.type = BubbleType.from_string(xlrd_row[TrackerContentCols.elem_type])
        self.sort_order = xlrd_row[TrackerContentCols.sort_order]
        self.paragraphs = {}
        self.add_row(xlrd_row)
    
    def add_row(self, xlrd_row):
        # This is assuming everything is already sorted in XLS
        self.paragraphs[len(self.paragraphs)] = Paragraph(xlrd_row)
    
    def ordered_paragraphs(self):
        return sorted(self.paragraphs.items(), key=lambda p: p[1].sort_order)

class Paragraph:
    number = 0
    business_logic = None
    translation_key = None
    link = None
    sort_order = 0
    
    def __init__(self, xlrd_row):
        if xlrd_row[TrackerContentCols.paragraph_num] != "":
            self.number = str(int(xlrd_row[TrackerContentCols.paragraph_num]))
        self.business_logic = xlrd_row[TrackerContentCols.business_logic]
        self.translation_key = xlrd_row[TrackerContentCols.trans_key]
        self.link = xlrd_row[TrackerContentCols.link]
        self.sort_order = xlrd_row[TrackerContentCols.sort_order]

class Translation:
    trans_key = None
    languages = {}
    
    def __init__(self, xlrd_row):
        self.trans_key = xlrd_row[TranslationCols.trans_key]
        self.languages = { 'English': xlrd_row[TranslationCols.content],
                           'Spanish': xlrd_row[TranslationCols.spanish],
                           'French': xlrd_row[TranslationCols.french],
                           'Urdu': xlrd_row[TranslationCols.urdu],
                           'Arabic': xlrd_row[TranslationCols.arabic],
                           'Bengali': xlrd_row[TranslationCols.bengali],
                           'Haitian': xlrd_row[TranslationCols.haitian],
                           'Russian': xlrd_row[TranslationCols.russian],
                           'Korean': xlrd_row[TranslationCols.korean],
                           'Chinese': xlrd_row[TranslationCols.chinese] }
    
    def get_language(self, lang):
        if not lang in self.languages:
            return None
        
        return self.languages[lang]

class Exporter:
    __walkthroughs = None
    __translations = None
    __wb_tracker = None
    __wb_translations = None
    
    def __init__(self):
        self.__walkthroughs = {}
        self.__translations = {}
        self.__wb_tracker = None
        self.__wb_translations = None
        pass
    
    def do_export(self):
        self.__open_workbooks()
        self.__load_walkthroughs()
        self.__load_translations()
        
        total_count = 0
        for key_w in sorted(self.__walkthroughs):
            walkthrough = self.__walkthroughs[key_w]
            print "{title} ({image_path})".format(title=walkthrough.title_key, image_path=walkthrough.teacher_image_caption)
            for key_p in walkthrough.ordered_pages():
                page = walkthrough.pages[key_p[0]]
                print "    {title} ({path})".format(title=page.title, path=page.server_image_path)
                for key_b in page.ordered_bubbles():
                    bubble = page.bubbles[key_b[0]]
                    print "        " + BubbleType.to_string(bubble.type)
                    for key_pa in bubble.ordered_paragraphs():
                        paragraph = bubble.paragraphs[key_pa[0]]
                        print "            " + str(paragraph.number)
                        total_count += 1
        
        print ""
        print ""
        print "Total count: " + str(total_count)
        """print ""
        print ""
        
        total_count = 0
        for key, obj in self.__translations.iteritems():
            print "{title}".format(title=obj.trans_key)
            total_count += 1
        
        print ""
        print ""
        print "Total count: " + str(total_count)"""
    
    
    def __open_workbooks(self):
        self.__wb_tracker = xlrd.open_workbook("../aplmerger/tracker.xls")
        self.__wb_translations = xlrd.open_workbook("../aplmerger/translation.xls")
    
    def __load_walkthroughs(self):
        # Load the rows from the content sheet
        sh_content = self.__wb_tracker.sheet_by_name("Content Sheet")
        for rownum in range(1, sh_content.nrows):
            row = sh_content.row_values(rownum)
            title = row[TrackerContentCols.title_key]
            if not title in self.__walkthroughs:
                #print title
                self.__walkthroughs[title] = Walkthrough(row)
            else:
                self.__walkthroughs[title].add_row(row)
        
        # Load the rows from the widgets and images sheet
        sh_widgets = self.__wb_tracker.sheet_by_name("Walkthrough Widgets And Images")
        for rownum in range(1, sh_widgets.nrows):
            row = sh_widgets.row_values(rownum)
            title = row[TrackerWidgetCols.title_key]
            if title in self.__walkthroughs:
                self.__walkthroughs[title].add_server_image_path(row)
        
        # Load the rows from the properties sheet
        sh_props = self.__wb_tracker.sheet_by_name("Walkthrough Properties")
        for rownum in range(1, sh_props.nrows):
            row = sh_props.row_values(rownum)
            title = row[TrackerPropertiesCols.title_key]
            if title in self.__walkthroughs:
                self.__walkthroughs[title].set_properties(row)
    
    def __load_translations(self):
        sh_origianl = self.__wb_translations.sheet_by_name("Origianl")
        for rownum in range(1, sh_origianl.nrows):
            row = sh_origianl.row_values(rownum)
            key = row[TranslationCols.trans_key]
            self.__translations[key] = Translation(row)

if __name__ == "__main__":
    exp = Exporter()
    exp.do_export()
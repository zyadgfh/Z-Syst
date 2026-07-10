import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import 'package:mobile_pos/Screens/Customers/Provider/customer_provider.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../currency.dart';
import 'package:mobile_pos/Screens/Customers/Model/parties_model.dart';

import 'view_ledger.dart';

class LedgerList extends StatefulWidget {
  const LedgerList({super.key});

  @override
  State<LedgerList> createState() => _LedgerListState();
}

class _LedgerListState extends State<LedgerList> {
  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        title: Text(
          lang.ledger,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(color: kWhite, fontWeight: FontWeight.w600, fontSize: 20),
        ),
        centerTitle: true,
        iconTheme: const IconThemeData(color: kWhite),
        elevation: 0.0,
      ),
      body: DefaultTabController(
        length: 3,
        child: Consumer(builder: (context, ref, __) {
          return Column(
            children: [
              Container(
                decoration: const BoxDecoration(color: kMainColorBg, borderRadius: BorderRadius.only(topRight: Radius.circular(30), topLeft: Radius.circular(30))),
                child: TabBar(
                  dividerColor: Colors.transparent,
                  indicatorSize: TabBarIndicatorSize.tab,
                  indicatorWeight: 2,
                  unselectedLabelStyle: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w500, color: kNutral700),
                  labelStyle: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w500, color: kMainColor),
                  tabs: [
                    Tab(text: lang.retailer),
                    Tab(text: lang.wholesaler),
                    Tab(text: lang.supplier),
                  ],
                ),
              ),
              Expanded(
                child: TabBarView(
                  children: [
                    _buildFilteredPartyList(ref, 'Retailer'),
                    _buildFilteredPartyList(ref, 'Wholesaler'),
                    _buildFilteredPartyList(ref, 'Supplier'),
                  ],
                ),
              ),
            ],
          );
        }),
      ),
    );
  }
}

Widget _buildFilteredPartyList(WidgetRef ref, String partyType) {
  return Padding(
    padding: const EdgeInsets.all(10),
    child: Consumer(
      builder: (context, ref, __) {
        final providerData = ref.watch(partiesProvider);
        Color getPartyColor(String type) {
          const partyColors = {
            'retailer': Color(0xFF56da87),
            'wholesaler': Color(0xFF25a9e0),
            'supplier': Color(0xFFA569BD),
          };
          return partyColors[type.toLowerCase()] ?? Colors.white;
        }

        // Helper function for avatar
        Widget buildAvatar(PartyModel party) {
          return CircleAvatar(
            backgroundColor: Colors.white,
            radius: 25.0,
            child: ClipOval(
              child: party.image != null
                  ? Image.network(
                      '${APIConfig.domain}${party.image}',
                      fit: BoxFit.cover,
                      width: 40.0,
                      height: 40.0,
                    )
                  : Container(
                      alignment: Alignment.center,
                      height: 40,
                      width: 40,
                      decoration: const BoxDecoration(
                        shape: BoxShape.circle,
                        color: kMainColor,
                      ),
                      child: Text(
                        ((party.name?.substring(0, 2)) ?? party.phone?.substring(0, 2) ?? '').toUpperCase(),
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              color: kWhite,
                              fontWeight: FontWeight.w500,
                            ),
                      ),
                    ),
            ),
          );
        }

        return providerData.when(
          data: (customers) {
            final filteredList = customers.where((party) => party.type == partyType).toList();

            return filteredList.isNotEmpty
                ? ListView.builder(
                    padding: EdgeInsets.zero,
                    itemCount: filteredList.length,
                    itemBuilder: (_, index) {
                      final party = filteredList[index];
                      final color = getPartyColor(party.type ?? '');

                      return ListTile(
                        onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => ViewLedgerScreen(
                              id: party.id ?? 0,
                              party: party,
                            ),
                          ),
                        ),
                        contentPadding: EdgeInsets.zero,
                        leading: SizedBox(
                          height: 50.0,
                          width: 50.0,
                          child: buildAvatar(party),
                        ),
                        title: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Flexible(
                              child: Text(
                                party.name ?? party.phone!,
                                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                                      color: kNutrals900,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                              ),
                            ),
                            SizedBox(width: 10),
                            if (party.due != null && party.due != 0)
                              Text(
                                '$currency ${party.due}',
                                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                                      color: kNutrals900,
                                    ),
                              ),
                            if (party.due != null && party.due == 0)
                              Text(
                                '$currency 0.0',
                                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                                      color: kNutrals900,
                                    ),
                              ),
                          ],
                        ),
                        subtitle: Row(
                          children: [
                            Text(
                              party.type ?? 'n/a',
                              style: Theme.of(context).textTheme.bodySmall?.copyWith(color: color),
                            ),
                            const Spacer(),
                            if (party.due != null && party.due != 0)
                              Text(
                                l.S.of(context).due,
                                style: Theme.of(context).textTheme.bodySmall?.copyWith(color: kSubTitleColor, fontSize: 12, fontWeight: FontWeight.w400),
                              ),
                            if (party.due != null && party.due == 0)
                              Text(
                                l.S.of(context).noDue,
                                style: Theme.of(context).textTheme.bodySmall?.copyWith(color: kMainColor, fontSize: 12, fontWeight: FontWeight.w400),
                              ),
                          ],
                        ),
                        trailing: const Icon(
                          Icons.arrow_forward_ios,
                          color: kNutral700,
                          size: 20,
                        ),
                      );
                    },
                  )
                : Center(
                    child: EmptyListWidget(title: l.S.of(context).listIsEmpty),
                  );
          },
          error: (e, stack) => Text(e.toString()),
          loading: () => const Center(child: CircularProgressIndicator()),
        );
      },
    ),
  );
}
